<?php

/**
 * Usage of Graylog simplyfied
 *
 * {snippet}
 *      \/* @var $graylog GraylogUtility *\/
 *      $graylog = getUtil('Graylog');
 *      $graylog->writeException($e);
 * {/snippet}
 *
 * @package metin2shop
 * @subpackage utilities
 * @author timo.huber
 * @version $Id: graylogutility.php 2994 2015-12-10 09:33:24Z timo.huber $
 */
class GraylogUtility
{

    /**
     * @var GF_Graylog
     */
    protected $_graylog = null;

    /**
     * @var null
     */
    protected $_locale = null;

    /**
     * Controller name
     *
     * @var string
     */
    protected $_controllerName = '';

    /**
     * Controller action
     *
     * @var string
     */
    protected $_action = '';

    /**
     * Track if the util still needs context data
     *
     * @var bool
     */
    protected $_isDirty = true;

    /**
     * @var array|null
     */
    protected $_nagiosIps = null;

    // -----------------

    public function __construct()
    {
        $this->_graylog = new GF_Graylog(GRAYLOG_HOST, GRAYLOG_PORT, GRAYLOG_FACILITY, GRAYLOG_THRESHOLD_LEVEL);
    }

    /**
     * Write Log to Graylog Server
     *
     * @author timo.huber
     *
     * @param string $shortMessage Short message
     * @param int $logLevel See Graylog:: constants
     * @param string $longMessage Long/Extended message
     * @param array $additional Additional fields/data
     */
    public function write($shortMessage, $logLevel = GF_Graylog::WARNING, $longMessage = '', array $additional = array())
    {
        if (!$this->_graylog) {
            return;
        }

        // KAZSHOP-988:
        // we only fire graylog events when we a live round or a devround in the debug mode
        if (DEVROUND == true && DEBUG_ENABLED == false) {
            return;
        }

        $basicInformation = $this->_getBasicInformation();
        $additional = array_merge($additional, $basicInformation);
        $this->_graylog->write($shortMessage, $logLevel, $longMessage, $additional);
    }

    /**
     * Write an exception to graylog
     * @author timo.huber
     * @param Exception $ex
     */
    public function writeException(Exception $ex)
    {
        if (!$this->_graylog) {
            return;
        }

        // KAZSHOP-988:
        // we only fire graylog events when we a live round or a devround in the debug mode
        if (DEVROUND == true && DEBUG_ENABLED == false) {
            return;
        }

        $basicInformation = $this->_getBasicInformation();

        $additional = array(
            '_exceptionClass' => get_class($ex),
            '_file' => $ex->getFile(),
            '_fileName' => basename($ex->getFile()),
            '_line' => $ex->getLine(),
            '_trace' => $ex->getTraceAsString(),
            '_logGroup' => 'exception',
        );

        $shortMessage = $ex->getMessage();
        $longMessage = $ex->getMessage();
        $logLevel = GF_Graylog::ERROR;
        $additional = array_merge($additional, $basicInformation);

        $this->_graylog->write($shortMessage, $logLevel, $longMessage, $additional);
    }

    /**
     * Set the locale component
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $this->_locale = $locale;
    }

    /**
     * Set the controller name
     *
     * @param $name
     */
    public function setControllerName($name)
    {
        $this->_controllerName = $name;
    }

    /**
     * Set the controller action
     * @param $action
     */
    public function setAction($action)
    {
        $this->_action = $action;

        $this->_updateDirty();
    }

    /**
     * Update the dirty-check (if we have all data)
     */
    protected function _updateDirty()
    {
        $this->_isDirty = !((bool) ($this->_locale && $this->_controllerName && $this->_action));
    }

    /**
     * Return if the util still needs data
     * @return bool
     */
    public function isDirty()
    {
        return $this->_isDirty;
    }

    /**
     * Return some information about the host and the installed version
     * @return array
     */
    protected function _getBasicInformation()
    {
        return array(
            '_host' => HOSTNAME,
            '_tag' => VERSION,
            '_language' => $this->_locale ? $this->_locale->getCode() : GAMELANGUAGE,
            '_game' => GAME,
            '_originController' => $this->_controllerName,
            '_originMethod' => $this->_action,
            '_project' => GAMENAME,
            '_isDev' => DEVROUND,
            '_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            '_requestUri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
        );
    }

    /**
     * Return an array of Nagios ips
     *
     * @return array
     *
     * @author timo.huber
     */
    public function getNagiosIps()
    {
        if ($this->_nagiosIps === null) {
            $this->_nagiosIps = array();

            if (!empty(NAGIOS_IPS)) {
                $this->_nagiosIps = array_combine(NAGIOS_IPS, NAGIOS_IPS);
            }
        }

        return $this->_nagiosIps;
    }
}//endclass
