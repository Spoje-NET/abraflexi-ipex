<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexiIpex package
 *
 * https://github.com/Spoje-NET/abraflexi-ipex
 *
 * (c) Vítězslav Dvořák <http://spojenet.cz/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
require_once file_exists('../vendor/autoload.php') ? '../vendor/autoload.php' : './vendor/autoload.php';

\Ease\Shared::init(
        [
            'IPEX_URL', 'IPEX_LOGIN', 'IPEX_PASSWORD',
            'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
        ],
        file_exists('../.env') ? '../.env' : './.env'
);
