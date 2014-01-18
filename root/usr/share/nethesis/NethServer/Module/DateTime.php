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

    private $tzValue = 'Greenwich';
    private $tzCodes = array();
    private $tzDatasource = array();

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
        $this->declareParameter('timezone', $this->createValidator(), array('configuration', 'TimeZone', NULL));
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);        

        // Every 60 seconds the view sends a query to refresh its date
        // and time controls, adding "tsquery" argument:
        if ( ! $request->getParameter('tsonly') !== NULL) {
            $this->initTzInfos();
        }

    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        if( $this->getRequest()->isMutation()) {
            $this->getValidator('timezone')->memberOf($this->tzCodes);
        }
        parent::validate($report);
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
        if($this->getRequest()->getParameter('tsonly') !== NULL) {
            $view['time'] = $this->parameters['time'];
            $view['date'] = $this->parameters['date'];
        } else {
            parent::prepareView($view);
            if ($view->getTargetFormat() !== $view::TARGET_JSON) {
                // optimize bandwidth for ajax requests by not sending tzDatasource:
                $view['timezoneDatasource'] = \Nethgui\Renderer\AbstractRenderer::hashToDatasource($this->tzDatasource);
            }
        }
        $view['current_datetime'] = sprintf("%s %s", $this->parameters['date'], $this->parameters['time']);
    }

    /**
     *
     * REQUIRE find command
     *
     */
    private function initTzInfos()
    {
        $zoneInfoDir = self::ZONEINFO_DIR;
        $tmp = $this->getPlatform()->exec('/usr/bin/find ${1} -maxdepth 1 -type d', array($zoneInfoDir))->getOutputArray();
        foreach($tmp as $area) {
           $acceptAreas[] = basename($area);
        }
        
        $cutpoint1 = strlen($zoneInfoDir);

        $zoneList = $this->getPlatform()->exec('/usr/bin/find ${1} -type f', array($zoneInfoDir))->getOutputArray();

        sort($zoneList);

        foreach ($zoneList as $zoneinfo) {
            $zoneinfo = substr($zoneinfo, $cutpoint1);
            $cutpoint2 = strpos($zoneinfo, '/');
            $area = substr($zoneinfo, 0, $cutpoint2);
            $this->tzCodes[] = $zoneinfo;
            if (in_array($area, $acceptAreas)) {
                $this->tzDatasource[$area][$zoneinfo] = str_replace('_', ' ', substr($zoneinfo, $cutpoint2 + 1));
            } else {
                $sparse[$zoneinfo] = str_replace('_', ' ', substr($zoneinfo, $cutpoint2));
            }
        }

        $this->tzDatasource['Posix'] = $sparse;

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