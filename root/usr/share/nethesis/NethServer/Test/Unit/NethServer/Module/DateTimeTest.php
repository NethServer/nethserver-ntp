<?php
namespace Test\Unit\NethServer\Module;
class DateTimeTest extends \Test\Tool\ModuleTestCase
{

    /**
     * @var NethServer\Module\DateTime
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new \NethServer\Module\DateTime;
    }

    public function environmentProvider()
    {
        $env1 = new \Test\Tool\ModuleTestEnvironment();
        $env1->setCommands(array(
            '|^/bin/date|' => '2010|12|31|12|00|30',
            '|^/usr/bin/find|' => strtr("%{PREFIX}Europe/Rome\n%{PREFIX}Europe/Berlin\n", array('%{PREFIX}' => \NethServer\Module\DateTime::ZONEINFO_DIR)),
            '|^/usr/bin/readlink|' => \NethServer\Module\DateTime::ZONEINFO_DIR . 'Europe/Rome',
        ));
        return array(array($env1));
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testNtpEnabledDefaults(\Test\Tool\ModuleTestEnvironment $env)
    {
        $cs = new \Test\Tool\DB();
        $cs->set($cs::getType('TimeZone'), ''); // missing default TimeZone value
        $cs->set($cs::getProp('ntpd', 'NTPServer'), '0.pool.ntp.org');
        $cs->set($cs::getProp('ntpd', 'status'), 'enabled');
        // DateTime module sets Europe/Rome by default:
        $cs->set($cs::setType('TimeZone', 'Europe/Rome'), TRUE);
        $cs->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setView(array(
            'status' => 'enabled',
            'server' => '0.pool.ntp.org',
            'date' => '2010-12-31',
            'time' => '12:00',
            'timezone' => 'Europe/Rome',
            'timezoneDatasource' => array(
                array(
                    array(
                        array('Europe/Berlin', 'Berlin'),
                        array('Europe/Rome', 'Rome')
                    ),
                    'Europe'
                )
            )
        ));


        $this->runModuleTest($this->object, $env);
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testNtpDisabledDefaults(\Test\Tool\ModuleTestEnvironment $env)
    {
        $cs = new \Test\Tool\DB();
        $cs->set($cs::getType('TimeZone'), 'Europe/Rome');
        $cs->set($cs::getProp('ntpd', 'NTPServer'), '');
        $cs->set($cs::getProp('ntpd', 'status'), 'disabled');
        $env->setDatabase('configuration', $cs);
        $cs->setFinal();
        $env->setView(array(
            'status' => 'disabled',
            'server' => '',
            'date' => '2010-12-31',
            'time' => '12:00',
            'timezone' => 'Europe/Rome'
        ));

        $this->runModuleTest($this->object, $env);
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testNtpEnable(\Test\Tool\ModuleTestEnvironment $env)
    {
        $cs = new \Test\Tool\DB();
        $cs->set($cs::getType('TimeZone'), 'Europe/Rome');
        $cs->set($cs::getProp('ntpd', 'NTPServer'), '');
        $cs->set($cs::getProp('ntpd', 'status'), 'disabled');

        // DB writes:
        $cs->transition($cs::setProp('ntpd', 'status', 'enabled'), TRUE)
            ->transition($cs::setProp('ntpd', 'NTPServer', '1.pool.ntp.org'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setRequest(array(
            'status' => 'enabled',
            'server' => '1.pool.ntp.org'
        ));

        $env->setView(array(
            'status' => 'enabled',
            'server' => '1.pool.ntp.org',
            'date' => '2010-12-31',
            'time' => '12:00',
            'timezone' => 'Europe/Rome'
        ));

        $env->setEvents(array(
            array('time-auto-update', array())
        ));

        $this->runModuleTest($this->object, $env);
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testNtpDisable(\Test\Tool\ModuleTestEnvironment $env)
    {
        $cs = new \Test\Tool\DB();
        $cs->set($cs::getType('TimeZone'), 'Europe/Rome');
        $cs->set($cs::getProp('ntpd', 'NTPServer'), '1.pool.ntp.org');
        $cs->set($cs::getProp('ntpd', 'status'), 'enabled');

        // DB writes:
        $cs->transition($cs::setProp('ntpd', 'status', 'disabled'), TRUE)->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setRequest(array(
            'status' => 'disabled',
            'server' => '1.pool.ntp.org',
            'date' => '2010-12-31',
            'time' => '12:00',
            'timezone' => 'Europe/Rome'
        ));

        $env->setView(array(
            'status' => 'disabled',
            'server' => '1.pool.ntp.org',
        ));

        $env->setEvents(array('time-manual-update'));

        $this->runModuleTest($this->object, $env);
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testChangeDate(\Test\Tool\ModuleTestEnvironment $env)
    {
        $cs = new \Test\Tool\DB();
        $cs->set($cs::getType('TimeZone'), 'Europe/Rome');
        $cs->set($cs::getProp('ntpd', 'NTPServer'), '');
        $cs->set($cs::getProp('ntpd', 'status'), 'disabled');
        $cs->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setRequest(array(
            'status' => 'disabled',
            'timezone' => 'Europe/Rome',
            'date' => '2011-01-31',
            'time' => '13:04'
        ));

        $env->setView(array(
            'date' => '2011-01-31',
            'time' => '13:04',
        ));

        $env->setEvents(array(
            array('time-manual-update', array('013113042011.00'))
        ));

        $this->runModuleTest($this->object, $env);
    }

}
