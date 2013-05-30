<?php
namespace NethServer\Module;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
 * 
 * This script is part of NethServer.
 * 
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

use Nethgui\System\PlatformInterface as Validate;

/**
 * Change the system time settings
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class DateTime extends \Nethgui\Controller\AbstractController
{
    const ZONEINFO_DIR = '/usr/share/zoneinfo/posix/';

    private $systemTimezone = 'Greenwich';

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 10);
    }

    public function initialize()
    {
        parent::initialize();
                                
        $this->declareParameter('status', Validate::SERVICESTATUS, array('configuration', 'ntpd', 'status'));        
        $this->declareParameter('date', Validate::DATE, array($this, 'getCurrentDate'));
        $this->declareParameter('time', Validate::TIME, array($this, 'getCurrentTime'));
        $this->declareParameter('server', Validate::HOSTNAME, array('configuration', 'ntpd', 'NTPServer'));

        $timezoneCodes = array();
        $timezoneDatasource = array();

        $this->fillTimezoneInfos($timezoneCodes, $timezoneDatasource, $this->systemTimezone);

        $this->declareParameter('timezone', $this->createValidator()->memberOf($timezoneCodes), array('configuration', 'TimeZone', NULL));

        $this->parameters['timezoneDatasource'] = \Nethgui\Renderer\AbstractRenderer::hashToDatasource($timezoneDatasource);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);        

        if ( ! $this->parameters['timezone']) {
            $this->parameters['timezone'] = $this->systemTimezone;
        }
    }


    /**
     * Parses `date` and `time` parameters and builds a timestamp for
     * the first argument to timezone-update event.
     * @return string The timestamp
     */
    public function provideTimestamp()
    {
        $dt = array();

        $dateParserRegexp = '(?P<year>\d\d\d\d)-(?P<month>\d\d)-(?P<day>\d\d)';

        if (preg_match('#' . $dateParserRegexp . ' (?P<hour>\d\d):(?P<minute>\d\d)#', trim($this->parameters['date']) . ' ' . trim($this->parameters['time']), $dt) > 0) {
            $timestamp = sprintf("%02d%02d%02d%02d%04d.%02d", $dt['month'], $dt['day'], $dt['hour'], $dt['minute'], $dt['year'], 0);
        } else {
            $timestamp = '';
        }

        return $timestamp;
    }

    protected function onParametersSaved($changedParameters)
    {
        if ($this->parameters['status'] === 'enabled') {
            $cond = array('status', 'server', 'timezone');
        } else {
            $cond = array('status', 'time', 'date', 'timezone');
        }

        if (count(array_intersect($cond, $changedParameters)) > 0) {
            $this->getPlatform()->signalEvent('nethserver-ntp-save@post-process', array(array($this, 'provideTimestamp')));
        }       
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if ($view->getTargetFormat() === $view::TARGET_JSON) {
            // optimize bandwidth for ajax requests by clearing timezoneDatasource:
            unset($this->parameters['timezoneDatasource']);
        }
        parent::prepareView($view);
    }

    /**
     *
     * REQUIRE find command
     *
     * @param array $timezoneCodes
     * @param array $timezoneDatasource
     * @param string $currentTimezone
     */
    private function fillTimezoneInfos(&$timezoneCodes, &$timezoneDatasource, &$currentTimezone)
    {
        $zoneInfoDir = self::ZONEINFO_DIR;
        $tmp = $this->getPlatform()->exec('/usr/bin/find ${1} -maxdepth 1 -type d', array($zoneInfoDir))->getOutputArray();
        foreach($tmp as $area) {
           $acceptAreas[] = basename($area);
        }
        
        $cutpoint1 = strlen($zoneInfoDir);

        $localtime = $zoneInfoDir . $this->getPlatform()->getDatabase('configuration')->getKey('TimeZone');
        $zoneList = $this->getPlatform()->exec('/usr/bin/find ${1} -type f', array($zoneInfoDir))->getOutputArray();

        sort($zoneList);

        foreach ($zoneList as $zoneinfo) {
            $zoneinfo = substr($zoneinfo, $cutpoint1);
            $cutpoint2 = strpos($zoneinfo, '/');
            $area = substr($zoneinfo, 0, $cutpoint2);
            $timezoneCodes[] = $zoneinfo;
            if (in_array($area, $acceptAreas)) {
                $timezoneDatasource[$area][$zoneinfo] = str_replace('_', ' ', substr($zoneinfo, $cutpoint2 + 1));
            } else {
                $sparse[$zoneinfo] = str_replace('_', ' ', substr($zoneinfo, $cutpoint2));
            }
            if ($localtime == $zoneinfo) {
                // found the current time zone
                $currentTimezone = $zoneinfo;
            }
        }
         $timezoneDatasource['Posix'] = $sparse;

        if ( ! $currentTimezone) {
            $currentTimezone = FALSE;
        }
    }

    private function getCurrentDateInfo()
    {
        static $dateInfo;

        if (is_null($dateInfo)) {
            $dateKeys = array('YYYY', 'mm', 'dd', 'HH', 'MM', 'SS');
            $dateValues = explode('|', $this->getPlatform()->exec('/bin/date ${1}', array('+%Y|%m|%d|%H|%M|%S'))->getOutput()) + array_fill(0, 6, '');
            $dateInfo = array_combine($dateKeys, $dateValues);
        }

        return $dateInfo;
    }

    public function getCurrentDate()
    {
        return strtr($this->getPlatform()->getDateFormat(), $this->getCurrentDateInfo());
    }

    public function getCurrentTime()
    {
        return strtr('HH:MM', $this->getCurrentDateInfo());
    }

}
