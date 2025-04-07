<?php

declare(strict_types=1);

namespace App\Resources\View\Components\FlashMessage;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;

#[Component(name: "flash-message", useDto: false)]
class FlashMessage extends BaseComponent
{
    public function render(): mixed
    {
        return $this->renderview(viewPath: "FlashMessage/FlashMessageView", data: [], loadFromModule: false);
    }
}
