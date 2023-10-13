<?php

namespace Boilerplate\Tools\Form;

class Writetous extends Form
{
    public function setFormCode(): string
    {
        return 'writetous' . $this->lang;
    }

    public function setFormMessage(): array
    {
        return [
            'title' => $this->content['form_response_title'],
            'text'  => $this->content['form_response_message'],
        ];
    }
}
