<?php

declare(strict_types=1);

namespace App\View\Resources\Components\Alert;

use Forge\Core\View\BaseComponent;
use Forge\Core\View\Component;
use App\Resources\View\Components\Alert\AlertPropsDto;

#[Component(name: "alert", useDto: true)]
class Alert extends BaseComponent
{
    public function __construct(AlertPropsDto $props)
    {
        parent::__construct($props);
    }

    public function render(): mixed
    {
        $data = [
            "alert" => $this->props,
        ];
        return $this->renderview(viewPath: "Alert/AlertView", data: $data, loadFromModule: false);
    }
}
