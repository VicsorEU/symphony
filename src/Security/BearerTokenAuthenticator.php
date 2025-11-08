<?php
namespace App\Security;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BearerTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $request->headers->get('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            throw new AuthenticationException('Missing Bearer token');
        }

        $token = substr($auth, 7);
        $repo  = $this->em->getRepository(ApiToken::class);
        $row   = $repo->findOneBy(['token' => $token]);

        if (!$row) {
            throw new AuthenticationException('Invalid token');
        }

        $userIdentifier = sprintf('%s:%s', $row->getRole(), (string)($row->getUserId() ?? ''));

        return new SelfValidatingPassport(
            new UserBadge($userIdentifier, function () use ($row) {
                return new class($row) implements UserInterface {
                    public function __construct(private ApiToken $t) {}
                    public function getRoles(): array
                    {
                        return [$this->t->getRole() === 'root' ? 'ROLE_ROOT' : 'ROLE_USER'];
                    }
                    public function getUserIdentifier(): string
                    {
                        return 'token_' . $this->t->getToken();
                    }
                    public function eraseCredentials(): void {}
                };
            })
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'unauthorized', 'message' => $exception->getMessage()], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}
