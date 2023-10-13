<?php

namespace Boilerplate\Tools\TextPage;

class Policy extends TextPage
{
    protected function getCode(): string
    {
        return 'policy';
    }

    protected function setData(): array
    {
        return $this->getPageData();
    }
}
