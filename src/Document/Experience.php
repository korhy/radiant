<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;


#[MongoDB\Document]
class Experience
{
    #[MongoDB\Id]
    protected $id;

    #[MongoDB\Field(type: 'string')]
    protected $company;

    #[MongoDB\Field(type: 'string')]
    protected $position;

    #[MongoDB\Field(type: 'collection')]
    protected $description;

    #[MongoDB\Field(type: 'string')]
    protected $url;

    #[MongoDB\Field(type: 'string')]
    protected $from;

    #[MongoDB\Field(type: 'string')]
    protected $to;

    #[MongoDB\Field(type: 'collection')]
    protected $tags;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): Experience
    {
        $this->company = $company;

        return $this;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function setDescription(array $description): Experience
    {
        $this->description = $description;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): Experience
    {
        $this->position = $position;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Experience
    {
        $this->url = $url;

        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): Experience
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo(string $to): Experience
    {
        $this->to = $to;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): Experience
    {
        $this->tags = $tags;

        return $this;
    }
}
