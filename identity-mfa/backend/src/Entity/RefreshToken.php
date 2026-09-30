<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_REFRESH_TOKEN', columns: ['refresh_token'])]
class RefreshToken extends AbstractRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected $id;

    #[ORM\Column(name: 'refresh_token', length: 128, unique: true)]
    protected $refreshToken;

    #[ORM\Column(length: 255)]
    protected $username;

    #[ORM\Column(type: 'datetime')]
    protected $valid;
}
