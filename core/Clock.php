<?php
/**
 * @file      polecat/core/Clock.php
 * @brief     Encapsulates micro-time functions for performance monitoring.
 *
 * Encapsulates micro-time functions to mimic behavior of ordinary
 * stop-watch with lap counter and some presets
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 
 */

if (!defined('ABLE_POLECAT_CORE')) {
  $able_polecat_path = __DIR__;
  define('ABLE_POLECAT_CORE', $able_polecat_path);
}
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Clock.php')));

class AblePolecat_Clock {

    /**
     * array key constants for times
     */
    const CLOCK_START_TIME = 'clock start time';
    const CLOCK_STOP_TIME = 'clock stop time';
    const CLOCK_START_SPLIT_TIME = 'clock start split time';
    const CLOCK_STOP_SPLIT_TIME = 'clock stop split time';
    const CLOCK_COUNTDOWN_TIME = 'clock countdown time';
    const CLOCK_SPLIT_TIMES = 'clock split times';
    const CLOCK_WAIT_INTERVAL_TIMES = 'clock wait interval times';

    /**
     * elapsed time benchmark constants
     */
    const ELAPSED_TIME_SINCE_CREATION = 0x0001;

    /**
     * elpased time calculator constants
     */
    const ELAPSED_TIME_TOTAL_COMBINED = 0x0001;
    const ELAPSED_TIME_TOTAL_ACTIVE = 0x0002;
    const ELAPSED_TIME_TOTAL_WAIT = 0x0004;

    /**
     * clock mode constants
     */
    const CLOCK_MODE_CHRONO = 0x0100;
    const CLOCK_MODE_COUNTDOWN = 0x0200;

    /**
     * countdown timer modes
     */
    const COUNTDOWN_MODE_SLEEP = 0x0010;
    const COUNTDOWN_MODE_POLL = 0x0020;
    const COUNTDOWN_MODE_INTERRUPT = 0x0030;

    /**
     * @var float
     */
    protected $_createTime;

    /**
     * @var array
     */
    protected $_times;

    /**
     * @var bool
     */
    //protected $_active;

    /**
     * @var int
     */
    protected $_mode;

    /**
     * @var string
     */
    protected $_countdownCallbackFuntionName;

    /**
     * @var string
     */
    protected $_countdownCallbackFuntionNameCallable;

    /**
     * @var array
     */
    protected $_countdownCallbackFuntionParameters;

    public function __construct () {
        $this->_createTime = $this->getMicroTime();
        $this->reset();
    }

    /**
     * reset
     *
     * Sets clock times to zero and re-initializes all presets
     */
    public function reset() {
        $now = $this->getMicroTime();
        $this->_times = array ( self::CLOCK_START_TIME => 0,
            self::CLOCK_STOP_TIME => $now,
            self::CLOCK_START_SPLIT_TIME => 0,
            self::CLOCK_STOP_SPLIT_TIME => 0,
            self::CLOCK_COUNTDOWN_TIME => 0,
            self::CLOCK_SPLIT_TIMES => array(),
            self::CLOCK_WAIT_INTERVAL_TIMES => array() );
        $this->_countdownCallbackFuntionName = null;
        $this->_countdownCallbackFuntionParameters = null;
        $this->_countdownCallbackFuntionNameCallable = null;
        $this->_mode = self::CLOCK_MODE_CHRONO;
    }


    /**
     * getElapsedTime
     *
     * Returns the total time elapsed in microseconds since the given benchmark time
     * NOTE: calling getElapsedTime while clock is running will create a split but will
     * not stop the clock
     *
     * @param int $timeSegment return total for active, wait or both times
     * @param bool $asString if TRUE returns time as a formatted string
     * @param int $benchMark time to use as the basis for calculating total time elapsed
     * @param float $givenTime RESERVED - TODO allow user to specifiy benchmark time
     * 
     * @return mixed the specified time total in microseconds as float or string or FALSE
     */
    public function getElapsedTime ( $timeSegment = self::ELAPSED_TIME_TOTAL_ACTIVE, 
        $asString = false,
        $benchMark = self::ELAPSED_TIME_SINCE_CREATION, 
        $givenTime = null ) {

        $markTime = $this->getMicroTime();
        $result = false;

        try {

            //
            // total logged times
            //
            switch ( $timeSegment ) {
                case self::ELAPSED_TIME_TOTAL_ACTIVE:
                    $result = array_sum( $this->_times[self::CLOCK_SPLIT_TIMES] );
                    if ( $this->isActive() ) {
                        //
                        // add time since last split start
                        //
                        $result += $markTime - $this->_times[self::CLOCK_START_SPLIT_TIME];
                    } break;
                case self::ELAPSED_TIME_TOTAL_WAIT:
                    $result = array_sum( $this->_times[self::CLOCK_WAIT_INTERVAL_TIMES] );
                    if ( $this->isActive() === false ) {
                        //
                        // haven't logged current wait time - add time since last stop
                        //
                        $result += $markTime - $this->_times[self::CLOCK_STOP_TIME];
                    } break;
                default:
                    //
                    // default is self::ELAPSED_TIME_TOTAL_COMBINED
                    //
                    $result += $markTime - $this->_createTime;
                    break;
            }

            switch ( $benchMark ) {
                default:
                    //
                    // default is self::ELAPSED_TIME_SINCE_CREATION
                    // this is TODO - adjust time to period other than
                    // when clock was created as basis for totals
                    //
                    break;
            }

            return $asString ? self::getMicrotimeString( $result ) : $result;
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to calculate elapsed time: " . $e->getMessage() );
            $result = false;
        }

        return $result;
    }

    /**
     * getRemainingTime
     *
     * If the clock is running in countdown mode AND the countdown mode is 'polling' 
     * call to getRemainingTime will return time left until countdown is complete,
     * otherwise it will return 0.
     *
     * @param bool $asString if TRUE returns time as a formatted string
     *
     * @return mixed time remaining until countdown is complete or 0 as float or string
     */
    public function getRemainingTime( $asString = false ) {

        $result = 0;

        try {
            if ( $this->_mode === self::CLOCK_MODE_COUNTDOWN ) {
                $result = $this->_times[self::CLOCK_COUNTDOWN_TIME] - $this->getElapsedTime ( self::ELAPSED_TIME_TOTAL_ACTIVE );
                if ( $result <= 0 ) {
                    //
                    // time's up - stop the clock if still running
                    //
                    $this->isActive() ? $this->stop() : null;
                    $result = 0;
                }
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to calculate remaining countdown time: " . $e->getMessage() );
        }

        return $asString ? self::getMicrotimeString( $result ) : $result;
    }

    /**
     * isActive
     *
     * @return bool TRUE if the clock is 'running' (keeping time) otherwise FALSE
     */
    public function isActive() {
        $result = ( isset ( $this->_times[self::CLOCK_START_TIME] ) && ( $this->_times[self::CLOCK_START_TIME] !== 0 ) );
        return $result;
    }

    /**
     * start
     *
     * sets the clock in motion (keeping time)
     *
     * in either mode (chrono or countdown) start() doubles as a 'resume' function if the
     * specified mode matches the internal mode, otherwise if modes do not match call to 
     * start will be considered 'reset and start over' (same behavior is achieved by setting 
     * $reset to TRUE).
     *
     * @param int $mode - the mode to start clock in (chrono or countdown)
     * @param int $countdownTime countdown time in seconds (ignored if mode is chrono)
     * @param bool $reset reset clock and start over
     *
     * @return mixed the current microtime or FALSE if the clock is already running or could not be started
     */
    public function start ( $mode = self::CLOCK_MODE_CHRONO, $countdownTime = 0, $reset = false ) {

        $result = false;

        try {
            if ( $this->isActive() === false ) {
                //
                // if mode change or command to reset, reset first
                //
                if ( ( $this->_mode !== $mode ) || $reset ) {
                    $this->reset();
                    $this->_mode = $mode;
                }

                //
                // setup countdown timer (if this is new start)
                //
                if ( ( $this->_mode === self::CLOCK_MODE_COUNTDOWN ) && 
                     ( $this->getRemainingTime() === 0 ) ) {
                    if ( $countdownTime > 0 ) {
                        $this->_times[self::CLOCK_COUNTDOWN_TIME] = $countdownTime;
                    } else {
                        $this->_times[self::CLOCK_COUNTDOWN_TIME] = 0;
                    }
                }
            }
            $result = $this->_restart();
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to start clock: " . $e->getMessage() );
            $this->reset();
            $result = false;
        }

        return $result;
    }

    /**
     * split
     *
     * logs a split (aka 'lap') time w/o stopping the clock
     */
    public function split () {

        $result = $this->getMicroTime();

        try {
            //
            // ignore split marker if clock is not running
            //
            if ( $this->isActive() ) {
                $this->_times[self::CLOCK_SPLIT_TIMES][] = 
                    $result - $this->_times[self::CLOCK_START_SPLIT_TIME];
                $this->_times[self::CLOCK_START_SPLIT_TIME] = $result;
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to capture split time: " . $e->getMessage() );
            $this->reset();
            $result = false;
        }

        return $result;
    }

    /**
     * stop
     *
     * stops the clock from ''running' (keeping time)
     *
     * Call to stop() is ignored if clock is not running
     *
     * @return mixed the current microtime or FALSE if the clock is already stopped or could not be stopped
     */
    public function stop () {

        $result = $this->split();

        try {
            //
            // ignore stop if already stopped
            //
            if ( $this->isActive() ) {
                //
                // log stop time
                //
                $this->_times[self::CLOCK_STOP_TIME] = $result;

                //
                // if mode is countdown, see if countdown time is up
                //
                if ( $this->_mode === self::CLOCK_MODE_COUNTDOWN ) {
                    $remainingCountdownTime = $this->_times[self::CLOCK_COUNTDOWN_TIME] - 
                        $this->getElapsedTime ( self::ELAPSED_TIME_TOTAL_ACTIVE );
                    if ( $remainingCountdownTime <= 0 ) {
                        //
                        // countdown time is up - adjust time log
                        //
                        $this->_adjustTimesOnCountdownCompletion();
                    }
                }

                //
                // setting start time to zero indicates clock is not running
                //
                $this->_times[self::CLOCK_START_TIME] = 0;
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to stop clock: " . $e->getMessage() );
            $this->reset();
            $result = false;
        }

        return $result;

    }

    /**
     * countdown
     *
     * starts clock in countdown mode
     *
     * Clock can be put into countdown mode in one of two ways
     * (1) call to countdown()
     * (2) call to start() specifying countdown as mode
     *
     * Countdown works in one of two ways:
     * (1) Sleep - puts script in sleep mode for countdown time and then executes callback if given
     * (2) Poll - caller starts countdown clock, continues script and is responsible for checking if
     *     countdown is complete. Callback can still be given but will not be invoked when countdown 
     *     is complete, rather when countdown is complete AND caller issues follow up call to function.
     * TODO: how to do interrupt???
     *
     * If clock is put into countdown mode by a call to start(), countdown mode defaults to poll and
     * no callback function can be given. countdown() allows caller to choose between available countdown
     * modes AND specify a callback function.
     *
     * If clock is running, successive calls to countdown() will be ignored.
     *
     * To check countdown time remaining in poll mode call getRemainingTime()
     *
     * @param int $countdownTime time to count down in seconds
     * @param int $mode sleep, poll, interrupt(?)
     * @param callback $functionName name of function to call when countdown is complete or NULL
     * @param array $parameters name-value pairs of callback function paramters/values or NULL
     *
     * @return mixed return value of callback function if mode is sleep or TRUE if mode is poll and countdown 
     * started successfully otherwise FALSE
     *
     */
    public function countdown ( $countdownTime, 
        $mode = self::COUNTDOWN_MODE_SLEEP, 
        $functionName = null, 
        $parameters = null ) {

        $result = false;

        try {
            if ( $this->isActive() === false ) {
                //
                // set up countdown callback
                //
                $this->_setCountdownCallback( $functionName, $parameters );

                //
                // start clock in given mode
                //
                if ( $mode === self::COUNTDOWN_MODE_SLEEP ) {
                    $this->start();
                    sleep ( $countdownTime );
                    $this->stop();

                    //
                    // do the callback
                    //
                    $result = $this->_countdownCallback();
                } else if ( $mode === self::COUNTDOWN_MODE_POLL ) {
                    $result = $this->start( self::CLOCK_MODE_COUNTDOWN, $countdownTime );
                } else {
                    throw new AblePolecat_Clock_Exception( "unknown countdown mode ($mode)" );
                }
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to countdown time: " . $e->getMessage() );
            $result = false;
        }

        return $result;
    }

    /**
     * getMicroTime
     *
     * @param bool $asString
     *
     * @return mixed returns the current Unix timestamp in microseconds
     */
    public static function getMicroTime ( $asString = false ) {
        list( $usec_, $sec_ ) = explode( ' ', microtime() );
        $return = ( (float)$usec_ + (float)$sec_ );
        return $asString ? self::getMicrotimeString( $return ) : $return;
	}

	/** 
     * getMicrotimeString
     *
	 *  @param float microtime
     *
     *  @return the time as a formatted string
	 */
    public static function getMicrotimeString( $microtime ) {
        return sprintf( '%.3f', $microtime )." sec";
    }

   /**
     * _restart
     *
     * checks if a start/stop has occured w/o reset and continues time keeping accordingly
     */
    protected function _restart () {

        $result = $this->getMicroTime();

        try {
            //
            // ignore restart if clock is already running
            //
            if ( $this->isActive() === false ) {
                //
                // if previous start/stop issued, log wait interval time
                //
                if ( $this->_times[self::CLOCK_STOP_TIME] > 0 ) {
                    $this->_times[self::CLOCK_WAIT_INTERVAL_TIMES][] =
                        $result - $this->_times[self::CLOCK_STOP_TIME];
                }
                //
                // set clock/split start time and run clock
                //
                $this->_times[self::CLOCK_START_TIME] = $result;
                $this->_times[self::CLOCK_START_SPLIT_TIME] = $result;
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "Failed to restart clock: " . $e->getMessage() );
            $this->reset();
            $result = false;
        }

        return $result;
    }

    /**
     * _setCountdownCallback
     *
     * Helper function to manage callback funtion for countdown timer feature
     */
    protected function _setCountdownCallback( $functionName, $parameters ) {

        if ( $this->isActive() === false ) {
            if ( is_callable( $functionName, false, $callableName ) ) {
                $this->_countdownCallbackFuntionName = $functionName;
                $this->_countdownCallbackFuntionNameCallable = $callableName;
            } else {
                $this->_countdownCallbackFuntionName = null;
                $this->_countdownCallbackFuntionNameCallable = null;
            } if ( !is_null ( $parameters ) && is_array ( $parameters ) ) {
                    $this->_countdownCallbackFuntionParameters = $parameters;
            } else {
                $this->_countdownCallbackFuntionParameters = array();
            }
        }
    }

    /**
     * _countdownCallback
     *
     * Helper function executes countdown callback and returns the result
     */
    protected function _countdownCallback() {

        $result = true;

        try {
            if ( isset ( $this->_countdownCallbackFuntionName ) ) {
                $result = call_user_func_array ( $this->_countdownCallbackFuntionName,
					$this->_countdownCallbackFuntionParameters );
            }
        } catch ( Exception $e ) {
            throw new AblePolecat_Clock_Exception( "callback failed: " . $e->getMessage() );
            $result = false;
        }

        return $result;
    }

    /**
     * _adjustTimesOnCountdownCompletion
     *
     * When countdown timer is started in polling mode, it is likely that time will run out
     * prior to countdown being stopped explicitly, by call to stop(), or implicitly, by call to 
     * getRemainingTime(). This function adjusts the logged times so that only specified countdown
     * period is counted as active and any elapsed time thereafter is wait time.
     */
    protected function _adjustTimesOnCountdownCompletion() {

        if ( isset ( $this->_times[self::CLOCK_SPLIT_TIMES] ) && 
             isset ( $this->_times[self::CLOCK_COUNTDOWN_TIME] ) )  {
            unset ( $this->_times[self::CLOCK_SPLIT_TIMES] );
            $this->_times[self::CLOCK_SPLIT_TIMES][] = $this->_times[self::CLOCK_COUNTDOWN_TIME];
        }
    }

}