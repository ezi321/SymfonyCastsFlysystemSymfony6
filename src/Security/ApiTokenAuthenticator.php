<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    const AUTH_KEY = 'Authorization';
    const BEARER   = 'Bearer ';

    private $apiTokenRepo;

    public function __construct(ApiTokenRepository $apiTokenRepo)
    {
        $this->apiTokenRepo = $apiTokenRepo;
    }

    public function supports(Request $request): bool
    {
        // look for header "Authorization: Bearer <token>"
        return $request->headers->has(self::AUTH_KEY)
            && str_starts_with($request->headers->get(self::AUTH_KEY), self::BEARER);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => $exception->getMessageKey()
        ], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): Response
    {
        return new JsonResponse([
            'token' => $token
        ], Response::HTTP_OK);
    }

    public function authenticate(Request $request): Passport
    {
        $tokenRepo     = $this->apiTokenRepo;
        $keyWithBearer = $request->headers->get(self::AUTH_KEY);
        $token         = str_replace(self::BEARER, '', $keyWithBearer);

        $credentials = new CustomCredentials(function($token, User $user) {
            $tokens = $user->getApiTokens();
            foreach ($tokens as $tok) {
                if($tok->getToken() === $token) {
                    if ($tok->isExpired()) {
                        throw new CustomUserMessageAuthenticationException(
                            'Token expired'
                        );
                    }
                    return true;
                }
            }
            return false;
        }, $token);

        return new Passport(new UserBadge($token, function ($token) use ($tokenRepo){
            $token = $tokenRepo->findOneBy(['token' => $token]);
            if (!$token) {
                throw new CustomUserMessageAuthenticationException(
                    'Invalid API Token'
                );
            }
            return $token->getUser();
        }), $credentials);
    }
}
