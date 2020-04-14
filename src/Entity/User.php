<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(
 * fields={"email"},
 * message="This email address has been taken by another account"
 *)
 */


class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(
     *      min = 5,
     *      max = 180,
     *      minMessage = "Email must be at least {{ limit }} characters",
     *      maxMessage = "Email cannot be more than {{ limit }} characters",
     *      allowEmptyString = false
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 5,
     *      max = 255,
     *      minMessage = "Password length must be at least {{ limit }}",
     *      maxMessage = "Password length cannot be more than {{ limit }}",
     *      allowEmptyString = false
     * )
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $last_name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $email_valid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email_token;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $registered_on;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="text")
     */
    private $about_me;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $profile_photo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $main_telephone_number;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $secondary_telephone_number;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getEmailValid(): ?int
    {
        return $this->email_valid;
    }

    public function setEmailValid(?int $email_valid): self
    {
        $this->email_valid = $email_valid;

        return $this;
    }

    public function getEmailToken(): ?string
    {
        return $this->email_token;
    }

    public function setEmailToken(?string $email_token): self
    {
        $this->email_token = $email_token;

        return $this;
    }

    public function getRegisteredOn(): ?\DateTimeInterface
    {
        return $this->registered_on;
    }

    public function setRegisteredOn(?\DateTimeInterface $registered_on): self
    {
        $this->registered_on = $registered_on;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getAboutMe(): ?string
    {
        return $this->about_me;
    }

    public function setAboutMe(string $about_me): self
    {
        $this->about_me = $about_me;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getProfilePhoto(): ?string
    {
        return $this->profile_photo;
    }

    public function setProfilePhoto(string $profile_photo): self
    {
        $this->profile_photo = $profile_photo;

        return $this;
    }

    public function getMainTelephoneNumber(): ?string
    {
        return $this->main_telephone_number;
    }

    public function setMainTelephoneNumber(string $main_telephone_number): self
    {
        $this->main_telephone_number = $main_telephone_number;

        return $this;
    }

    public function getSecondaryTelephoneNumber(): ?string
    {
        return $this->secondary_telephone_number;
    }

    public function setSecondaryTelephoneNumber(string $secondary_telephone_number): self
    {
        $this->secondary_telephone_number = $secondary_telephone_number;

        return $this;
    }
}
