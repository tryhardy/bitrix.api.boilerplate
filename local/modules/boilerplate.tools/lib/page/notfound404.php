<?php

namespace Boilerplate\Tools\Page;

use Boilerplate\Tools\Helper;

class NotFound404 extends Page
{
    protected function getCode(): string
    {
        return 'notfound404';
    }

    protected function setData(): array
    {
        $a = [];

        if ($this->content['meta']['meta_title']) {
            $a['meta']['title'] = $this->content['meta']['meta_title'];
        }

        if ($this->content['meta']['meta_description']) {
            $a['meta']['description'] = $this->content['meta']['meta_description'];
        }

        if ($this->content['notfound404']['title']) {
            $a['meta']['h1'] = $this->content['notfound404']['title'];
            $a['title'] = $this->content['notfound404']['title'];
        }

        if ($this->content['notfound404']['text']) {
            $a['text'] = $this->content['notfound404']['text'];
        }

        if ($this->content['notfound404']['link_href']
            && $this->content['notfound404']['link_text']) {
            $a['link'] = [
                'href' => $this->content['notfound404']['link_href'],
                'text' => $this->content['notfound404']['link_text'],
            ];
        }

        return $a;
    }
}
