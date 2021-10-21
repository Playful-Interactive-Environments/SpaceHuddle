<?php

namespace App\Routing;

use Cake\Chronos\Chronos;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;

/**
 * JwtAuth.
 */
final class JwtAuth
{
    /**
     * @var Configuration
     */
    private Configuration $configuration;
    /**
     * @var string The issuer name
     */
    private string $issuer;
    /**
     * @var int Max lifetime in seconds
     */
    private int $lifetime;

    /**
     * The constructor.
     **
     * @param Configuration $configuration
     * @param string $issuer The issuer name
     * @param int $lifetime The max lifetime in seconds
     */
    public function __construct(Configuration $configuration, string $issuer, int $lifetime)
    {
        $this->configuration = $configuration;
        $this->issuer = $issuer;
        $this->lifetime = $lifetime;
    }

    /**
     * Get JWT max lifetime.
     *
     * @return int The lifetime in seconds
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Create JSON web token.
     **
     * @param array<string> $claims The claims
     **
     * @return string The JWT
     */
    public function createJwt(array $claims): string
    {
        $now = Chronos::now();
        $builder = $this->configuration->builder()
            // Configures the issuer (iss claim)
            ->issuedBy($this->issuer)
            // Configures the id (jti claim)
            ->identifiedBy(uuid_create())
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->addSeconds($this->lifetime));
        // Add claims like "uid"
        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }
        // Builds a new token using the private key
        return $builder->getToken(
            $this->configuration->signer(),
            $this->configuration->signingKey()
        )->toString();
    }

    /**
     * Parse token.
     **
     * @param string $token The JWT
     **
     * @return UnencryptedToken The parsed token
     * @throws ConstraintViolation
     **
     */
    public function createParsedToken(string $token): UnencryptedToken
    {
        $token = $this->configuration->parser()->parse($token);
        if (!$token instanceof UnencryptedToken) {
            throw new ConstraintViolation("You should pass a plain token");
        }
        return $token;
    }

    /**
     * Validate the access token.
     **
     * @param string $accessToken The JWT
     **
     * @return UnencryptedToken|null The token, if valid
     */
    public function validateToken(string $accessToken): ?UnencryptedToken
    {
        $token = $this->createParsedToken($accessToken);
        $constraints = $this->configuration->validationConstraints();
        // Token signature must be valid
        $constraints[] = new SignedWith(
            $this->configuration->signer(),
            $this->configuration->verificationKey()
        );
        // Check whether the issuer is the same
        $constraints[] = new IssuedBy($this->issuer);
        // Check whether the token has not expired
        $constraints[] = new LooseValidAt(new SystemClock(Chronos::now()->getTimezone()));
        if (!$this->configuration->validator()->validate($token, ...$constraints)) {
            // Token signature is not valid
            return null;
        }
        // Custom constraints
        // Check whether the user id and username are valid
        // TODO: Do we need to check against the database here if these are valid claims (userId and username)?
        $userId = $token->claims()->get("userId");
        $username = $token->claims()->get("username");
        $isUser = (isset($userId) and isset($username));

        $participantId = $token->claims()->get("participantId");
        $browserKey = $token->claims()->get("browserKey");
        $isParticipant = (isset($participantId) and isset($browserKey));

        if ($isUser and $isParticipant) {
            // Token related to an unknown user and participant
            return null;
        }
        return $token;
    }
}
