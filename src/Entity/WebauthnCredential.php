<?php

declare(strict_types = 1);

namespace App\Entity;

use Symfony\Component\Uid\Uuid;
use App\Repository\WebauthnCredentialSourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;

#[Table(name: 'webauthn_credentials')]
#[Entity(repositoryClass: WebauthnCredentialSourceRepository::class)]
class WebauthnCredential extends PublicKeyCredentialSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        string      $publicKeyCredentialId,
        string      $type,
        array       $transports,
        string      $attestationType,
        TrustPath   $trustPath,
        Uuid $aaguid,
        string      $credentialPublicKey,
        string      $userHandle,
        int         $counter
    ) {
        parent::__construct($publicKeyCredentialId, $type, $transports, $attestationType, $trustPath, $aaguid, $credentialPublicKey, $userHandle, $counter);
    }
}
