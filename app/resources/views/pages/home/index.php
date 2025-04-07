<?php

use Forge\Core\View\Component;

/**
 * @var string $title
 */

layout(name: "main", loadFromModule: false);
?>
<div class="layout-wrapper">
    <div class="landing-wrapper">
        <div class="landing-container">
            <h1>
                <p class="forge-logo">Forge</p>
            </h1>

            <p class="forge-welcome-text">
                Welcome to Forge! You've successfully installed the core of your new PHP framework. <br>
                Get ready to build something amazing, entirely on your terms.
            </p>

            <?= Component::render(name: "nav-bar", loadFromModule: false, props: []) ?>

            <?= Component::render(name: "footer", loadFromModule: false, props: []) ?>
        </div>
    </div>
</div>