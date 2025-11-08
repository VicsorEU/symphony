<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('/v1/api/users', name: 'users_entry', methods: ['GET','POST','PUT','DELETE'])]
    public function handle(Request $request): JsonResponse
    {
        try {
            return match ($request->getMethod()) {
                'GET'    => $this->apiGet($request),
                'POST'   => $this->apiCreate($request),
                'PUT'    => $this->apiUpdate($request),
                'DELETE' => $this->apiDelete($request),
                default  => new JsonResponse(['error' => 'method_not_allowed'], 405),
            };
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }


    private function currentToken(): ?ApiToken
    {
        $req  = Request::createFromGlobals();
        $auth = $req->headers->get('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            return null;
        }
        $token = substr($auth, 7);
        return $this->em->getRepository(ApiToken::class)->findOneBy(['token' => $token]);
    }

    private function denyIfNotAllowed(?int $targetUserId, string $method): ?JsonResponse
    {
        $tok = $this->currentToken();
        if (!$tok) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $role = $tok->getRole();
        if ($role === 'root') {
            return null;
        }

        if ($method === 'DELETE') {
            return new JsonResponse(['error' => 'forbidden', 'message' => 'user role cannot delete'], 403);
        }
        $myId = $tok->getUserId();
        if (!$myId || $targetUserId === null || $myId !== $targetUserId) {
            return new JsonResponse(['error' => 'forbidden', 'message' => 'user role limited to own id'], 403);
        }
        return null;
    }


    private function apiGet(Request $r): JsonResponse
    {
        $id = $r->query->getInt('id', 0);
        if ($id <= 0) {
            return new JsonResponse(['error' => 'validation', 'message' => 'id is required'], 400);
        }

        if ($denied = $this->denyIfNotAllowed($id, 'GET')) {
            return $denied;
        }

        $u = $this->em->getRepository(User::class)->find($id);
        if (!$u) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        return new JsonResponse([
            'login' => $u->getLogin(),
            'pass'  => $u->getPass(),
            'phone' => $u->getPhone(),
        ]);
    }

    private function apiCreate(Request $r): JsonResponse
    {
        $data  = $r->getContentTypeFormat() === 'json'
            ? json_decode($r->getContent(), true)
            : $r->request->all();

        $login = $data['login'] ?? null;
        $pass  = $data['pass']  ?? null;
        $phone = $data['phone'] ?? null;

        if (!$login || !$pass || !$phone) {
            return new JsonResponse(['error' => 'validation', 'message' => 'login, pass, phone are required'], 400);
        }

        $tok = $this->currentToken();
        if ($tok && $tok->getRole() === 'user' && $tok->getUserId()) {
            return new JsonResponse(['error' => 'forbidden', 'message' => 'user role cannot create other users'], 403);
        }

        $u = (new User())
            ->setLogin($login)
            ->setPass($pass)
            ->setPhone($phone);

        $errors = $this->validator->validate($u);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'validation', 'message' => (string)$errors], 400);
        }

        $exists = $this->em->getRepository(User::class)->findOneBy(['login' => $login, 'pass' => $pass]);
        if ($exists) {
            return new JsonResponse(['error' => 'duplicate', 'message' => '(login,pass) must be unique'], 409);
        }

        $this->em->persist($u);
        $this->em->flush();

        if ($tok && $tok->getRole() === 'user' && !$tok->getUserId()) {
            $tok->setUserId($u->getId());
            $this->em->flush();
        }

        return new JsonResponse([
            'id'    => $u->getId(),
            'login' => $u->getLogin(),
            'pass'  => $u->getPass(),
            'phone' => $u->getPhone(),
        ], 201);
    }

    private function apiUpdate(Request $r): JsonResponse
    {
        $data  = $r->getContentTypeFormat() === 'json'
            ? json_decode($r->getContent(), true)
            : $r->request->all();

        $id    = isset($data['id']) ? (int)$data['id'] : 0;
        $login = $data['login'] ?? null;
        $pass  = $data['pass']  ?? null;
        $phone = $data['phone'] ?? null;

        if ($id <= 0 || !$login || !$pass || !$phone) {
            return new JsonResponse(['error' => 'validation', 'message' => 'id, login, pass, phone are required'], 400);
        }

        if ($denied = $this->denyIfNotAllowed($id, 'PUT')) {
            return $denied;
        }

        $u = $this->em->getRepository(User::class)->find($id);
        if (!$u) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        $u->setLogin($login)->setPass($pass)->setPhone($phone);

        $dup = $this->em->getRepository(User::class)->findOneBy(['login' => $login, 'pass' => $pass]);
        if ($dup && $dup->getId() !== $id) {
            return new JsonResponse(['error' => 'duplicate', 'message' => '(login,pass) must be unique'], 409);
        }

        $errors = $this->validator->validate($u);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => 'validation', 'message' => (string)$errors], 400);
        }

        $this->em->flush();

        return new JsonResponse(['id' => $u->getId()]);
    }

    private function apiDelete(Request $r): JsonResponse
    {
        $id = $r->query->getInt('id', 0);
        if ($id <= 0) {
            return new JsonResponse(['error' => 'validation', 'message' => 'id is required'], 400);
        }

        if ($denied = $this->denyIfNotAllowed($id, 'DELETE')) {
            return $denied;
        }

        $u = $this->em->getRepository(User::class)->find($id);
        if (!$u) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        $this->em->remove($u);
        $this->em->flush();

        return new JsonResponse([], 200);
    }
}
