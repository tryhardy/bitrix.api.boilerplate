<?php

namespace Boilerplate\Tools\Page;

use Boilerplate\Tools\Helper;

class NotFound404 extends Page
{
    protected function getCode(): string
    {
        return 'notfound404';
    }

	protected function getMeta() : array
	{
		$meta = [];

		if ($val = $this->content['meta']['meta_title']) {
			$meta['title'] = $val;
		}

        if ($val = $this->content['meta']['meta_description']) {
	        $meta['description'] = $val;
        }

        if ($val = $this->content['meta']['title']) {
	        $meta['h1'] = $val;
        }

		return $meta;
	}

	protected function getContent()
	{
		$content = [];
		
		if ($val = $this->content['content']['text']) {
			$content['text'] = $val;
		}

		if ($this->content['content']['link_href'] && $this->content['content']['link_text']) {
			$content['link'] = [
				'href' => $this->content['content']['link_href'],
				'text' => $this->content['content']['link_text'],
			];
		}

		return $content;
	}
}
