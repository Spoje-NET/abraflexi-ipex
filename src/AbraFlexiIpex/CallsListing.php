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

namespace SpojeNet\AbraFlexiIpex;

/**
 * Description of CallsListing.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class CallsListing extends \Ease\Html\TableTag
{
    /**
     * Calls Listing table.
     *
     * @param array<int, array<string, int|string>> $calls
     * @param array<string, string>                 $properties
     */
    public function __construct($calls = null, $properties = [])
    {
        parent::__construct(null, $properties);

        if (null !== $calls) {
            $this->populate($calls);
        }
    }

    /**
     * @param array<int, array<string, int|string>> $calls
     */
    public function populate(/* array */ $calls) // : self
    {
        // cislo_int
        // cislo_ext
        // destinace
        // datetime
        // odchozi
        // placeny
        // stav
        // delka
        // jednotka
        // cena
        // sazba_dph
        // pocet
        // cdr_flags
        // zakaznik
        // zakaznik_id
        $this->addRowHeaderColumns([
            _('Calling number'),
            _('Called number'),
            _('Destination'),
            _('When'),
            _('Direction'),
            _('Payed'),
            _('State'),
            _('Duration'),
            //            _('Unit'),
            _('Price'),
            _('Vat value'),
        ]);

        foreach ($calls as $callData) {
            $this->addRowColumns([
                $callData['cislo_int'],
                $callData['cislo_ext'],
                $callData['destinace'],
                $callData['datetime'],
                ($callData['odchozi'] === 'Y') ? _('Outcomming') : _('Incoming'),
                ($callData['placeny'] === 'Y') ? _('Yes') : _('No'),
                ($callData['stav'] === 'Zodpovězeno') ? _('Answered') : _('Not Answered'),
                $this->secsToStr((int) $callData['delka']),
                //                $callData['jednotka'],
                $callData['cena'],
                $callData['sazba_dph'],
            ]);
        }

        return $this;
    }

    /**
     * Seconds to human readable.
     *
     * @param float|int $duration
     */
    public function secsToStr($duration): string
    {
        $periods = [
            _('day') => 86400,
            _('hour') => 3600,
            _('minute') => 60,
            _('second') => 1,
        ];

        $parts = [];

        foreach ($periods as $name => $dur) {
            $div = floor($duration / $dur);

            if (empty($div)) {
                continue;
            }

            if ((int) $div === 1) {
                $parts[] = $div.' '.$name;
            } else {
                $parts[] = $div.' '.$name.'s';
            }

            $duration %= $dur;
        }

        if (empty($parts)) {
            return '0 '._('seconds');
        }

        $last = array_pop($parts);

        return empty($parts) ? $last : implode(', ', $parts).' '._('And').' '.$last;
    }
}
