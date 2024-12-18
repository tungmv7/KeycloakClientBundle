<?php

declare(strict_types=1);

namespace Mainick\KeycloakClientBundle\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Mainick\KeycloakClientBundle\Exception\TokenDecoderException;
use Mainick\KeycloakClientBundle\Interface\TokenDecoderInterface;

class RS256TokenDecoder implements TokenDecoderInterface
{
    public function decode(string $token, string $key): array
    {
        try {
            $publicKeyPem = <<<EOD
-----BEGIN PUBLIC KEY-----
$key
-----END PUBLIC KEY-----
EOD;
            $publicKey = openssl_get_publickey($publicKeyPem);

            $headers = new \stdClass();
            $tokenDecoded = JWT::decode($token, new Key($publicKey, 'RS256'), $headers);

            $json = json_encode($tokenDecoded, JSON_THROW_ON_ERROR);

            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\Exception $e) {
            throw new TokenDecoderException('Error decoding token', $e);
        }
    }

    public function validateToken(string $realm, array $tokenDecoded): void
    {
        $now = time();

        if ($tokenDecoded['exp'] < $now) {
            throw TokenDecoderException::forExpiration(new \Exception('Token has expired'));
        }

        if (str_contains($tokenDecoded['iss'], $realm) === false) {
            throw TokenDecoderException::forIssuerMismatch(new \Exception('Invalid token issuer'));
        }
    }
}
