<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use LogicException;
use Random\RandomException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Exception\InvalidDataException;
use Webauthn\Bundle\Repository\CanGenerateUserEntity;
use Webauthn\Bundle\Repository\CanRegisterUserEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;

final readonly class WebauthnCredentialUserRepository implements
    PublicKeyCredentialUserEntityRepositoryInterface,
    CanRegisterUserEntity,
    CanGenerateUserEntity
{
    public function __construct(
        private UserRepository $userRepository,
        private Connection $connection
    ) {
    }

    /**
     * @throws Exception
     * @see https://dba.stackexchange.com/q/253090
     * @see https://dba.stackexchange.com/a/253098
     * @todo using UUIDs would be a better idea as they are decoupled from the database
     */
    public function generateNextUserEntityId(): string
    {
        return (string) $this->connection
            ->executeQuery('SELECT last_value + 1 FROM user_id_seq;')
            ->fetchOne();
    }

    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity): void
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['id' => $userEntity->id]);

        if ($user === null) {
            $user = (new User())
                ->setId($userEntity->id)
                ->setEmail($userEntity->name)
                ->setRoles(['ROLE_USER']);
        } else {
            if ($user->getUserIdentifier() !== $userEntity->name) {
                $user->setEmail($userEntity->name);
            }
        }

        $this->userRepository->save($user);
    }

    /**
     * @throws InvalidDataException
     */
    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $username]);

        return $this->getUserEntity($user);
    }

    /**
     * @throws InvalidDataException
     */
    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['id' => $userHandle]);

        return $this->getUserEntity($user);
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function generateUserEntity(?string $username, ?string $displayName): PublicKeyCredentialUserEntity
    {
        $randomUserData = Base64UrlSafe::encodeUnpadded(random_bytes(32));

        return PublicKeyCredentialUserEntity::create(
            $username ?? $randomUserData,
            $this->generateNextUserEntityId(),
            $displayName ?? $username ?? $randomUserData,
            null
        );
    }

    /**
     * @throws InvalidDataException
     */
    private function getUserEntity(null|User $user): ?PublicKeyCredentialUserEntity
    {
        if ($user === null) {
            return null;
        }

        return new PublicKeyCredentialUserEntity(
            $user->getUserIdentifier(),
            (string)$user->getId(),
            $user->getDisplayName(),
            null
        );
    }
}
