<?php

namespace App\Service\NewsApi;

class NewsRequestItem
{
    private $title;
    private $text;
    private $url;
    private $date;
    private $category;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
