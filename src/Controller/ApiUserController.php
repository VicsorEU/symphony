<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;


#[Route('/v1/api/users')]
class ApiUserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'users_entry', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function index(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json(['error' => 'auth', 'message' => 'Missing Bearer token'], 401);
        }

        $tokenValue = substr($authHeader, 7);
        /** @var ApiToken|null $token */
        $token = $this->em->getRepository(ApiToken::class)->findOneBy(['token' => $tokenValue]);
        if (!$token) {
            return $this->json(['error' => 'auth', 'message' => 'Invalid token'], 403);
        }

        $role   = $token->getRole();
        $method = $request->getMethod();

        if ($method === 'GET') {
            $id = $request->query->getInt('id', 0);
            if ($id <= 0) {
                return $this->json([
                    'error'   => 'validation',
                    'message' => 'Parameter "id" is required in GET request, e.g. /v1/api/users?id=3'
                ], 400);
            }

            $u = $this->em->getRepository(User::class)->find($id);
            if (!$u) {
                return $this->json(['error' => 'not_found', 'message' => 'User not found'], 404);
            }

            if ($role !== 'root' && $u->getCreatedByToken() !== (int) $token->getId()) {
                return $this->json(['error' => 'forbidden', 'message' => 'Cannot read user created by another token'], 403);
            }

            return $this->json([
                'id'                => $u->getId(),
                'login'             => $u->getLogin(),
                'phone'             => $u->getPhone(),
                'pass'              => $u->getPass(),
                'created_by_token'  => $u->getCreatedByToken(),
            ]);
        }

        if ($method === 'POST') {
            $data = json_decode($request->getContent(), true) ?? [];
            if (!isset($data['login'], $data['pass'], $data['phone'])) {
                return $this->json(['error' => 'validation', 'message' => 'login, pass, phone are required'], 400);
            }

            $login = (string)$data['login'];
            $pass  = (string)$data['pass'];
            $phone = (string)$data['phone'];

            $repo = $this->em->getRepository(User::class);
            if ($repo->findOneBy(['login' => $login])) {
                return $this->json(['error' => 'duplicate', 'field' => 'login', 'message' => 'User with this login already exists'], 409);
            }
            if ($repo->findOneBy(['pass' => $pass])) {
                return $this->json(['error' => 'duplicate', 'field' => 'pass', 'message' => 'User with this pass already exists'], 409);
            }
            if ($repo->findOneBy(['login' => $login, 'pass' => $pass])) {
                return $this->json(['error' => 'duplicate', 'field' => 'login+pass', 'message' => 'User with this login/pass already exists'], 409);
            }

            $user = (new User())
                ->setLogin($login)
                ->setPass($pass)
                ->setPhone($phone);

            if ($role === 'root') {
                $user->setCreatedByToken(0);
            } else {
                $user->setCreatedByToken((int)$token->getId());
            }

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                return $this->json(['error' => 'validation', 'message' => (string)$errors], 400);
            }

            try {
                $this->em->persist($user);
                $this->em->flush();
            } catch (UniqueConstraintViolationException $e) {
                return $this->json(['error' => 'duplicate', 'message' => 'User already exists (unique constraint)'], 409);
            }

            return $this->json([
                'id'               => $user->getId(),
                'login'            => $user->getLogin(),
                'phone'            => $user->getPhone(),
                'pass'             => $user->getPass(),
                'created_by_token' => $user->getCreatedByToken(),
            ], 201);
        }


        if ($method === 'PUT') {
            $data = json_decode($request->getContent(), true) ?? [];
            if (!isset($data['id'])) {
                return $this->json(['error' => 'validation', 'message' => 'id is required for update'], 400);
            }

            $u = $this->em->getRepository(User::class)->find((int)$data['id']);
            if (!$u) {
                return $this->json(['error' => 'not_found', 'message' => 'User not found'], 404);
            }

            if ($role !== 'root' && $u->getCreatedByToken() !== (int)$token->getId()) {
                return $this->json(['error' => 'forbidden', 'message' => 'Cannot edit user created by another token'], 403);
            }

            $repo = $this->em->getRepository(User::class);

            if (isset($data['login'])) {
                $login = (string)$data['login'];
                $other = $repo->findOneBy(['login' => $login]);
                if ($other && $other->getId() !== $u->getId()) {
                    return $this->json([
                        'error'   => 'duplicate',
                        'field'   => 'login',
                        'message' => 'User with this login already exists'
                    ], 409);
                }
                $u->setLogin($login);
            }

            if (isset($data['pass'])) {
                $pass = (string)$data['pass'];
                $other = $repo->findOneBy(['pass' => $pass]);
                if ($other && $other->getId() !== $u->getId()) {
                    return $this->json([
                        'error'   => 'duplicate',
                        'field'   => 'pass',
                        'message' => 'User with this pass already exists'
                    ], 409);
                }
                $u->setPass($pass);
            }

            if (isset($data['login']) || isset($data['pass'])) {
                $login = $u->getLogin();
                $pass  = $u->getPass();
                $other = $repo->findOneBy(['login' => $login, 'pass' => $pass]);
                if ($other && $other->getId() !== $u->getId()) {
                    return $this->json([
                        'error'   => 'duplicate',
                        'field'   => 'login+pass',
                        'message' => 'User with this login/pass combination already exists'
                    ], 409);
                }
            }

            if (isset($data['phone'])) {
                $u->setPhone((string)$data['phone']);
            }

            $errors = $validator->validate($u);
            if (count($errors) > 0) {
                return $this->json(['error' => 'validation', 'message' => (string)$errors], 400);
            }

            try {
                $this->em->flush();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                return $this->json([
                    'error'   => 'duplicate',
                    'message' => 'User already exists (unique constraint)'
                ], 409);
            }

            return $this->json(['message' => 'User updated']);
        }

        if ($method === 'DELETE') {
            if ($role !== 'root') {
                return $this->json(['error' => 'forbidden', 'message' => 'Only root can delete users'], 403);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            if (!isset($data['id'])) {
                return $this->json(['error' => 'validation', 'message' => 'id is required for delete'], 400);
            }

            $u = $this->em->getRepository(User::class)->find((int) $data['id']);
            if (!$u) {
                return $this->json(['error' => 'not_found', 'message' => 'User not found'], 404);
            }

            $this->em->remove($u);
            $this->em->flush();
            return $this->json(['message' => 'User deleted']);
        }

        return $this->json(['error' => 'method', 'message' => 'Unsupported method'], 405);
    }
}
