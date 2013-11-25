<?php
/**
 * @file: Error.php
 * Almost entirely data: Able Polecat default error codes/messages.
 */

class AblePolecat_Error {

  /**
   * Able Polecat core error codes.
   */
  const NO_ERROR_CODE_GIVEN           = 10000;
  const UNSUPPORTED_PHP_VER           = 10001;
  const BOOT_SEQ_VIOLATION            = 10005;
  const SYS_PATH_ERROR                = 10010;
  const BOOT_PATH_INVALID             = 10015;
  const BOOTSTRAP_CLASS_REG           = 10020;
  const BOOTSTRAP_LOGGER              = 10025;
  const BOOTSTRAP_AGENT               = 10030;
  const BOOTSTRAP_CONFIG              = 10035;
  const BOOTSTRAP_SESSION             = 10040;
  const BOOTSTRAP_DB                  = 10045;
  const BOOTSTRAP_BUS                 = 10050;
  const NO_ENVIRONMENT_CONFIG         = 10055;
  const MKDIR_FAIL                    = 10061;
  const ACCESS_INVALID_OBJECT         = 10060;
  const LIBS_PATH_INVALID             = 10065;
  const LOGS_PATH_INVALID             = 10070;
  const MODS_PATH_INVALID             = 10075;
  const UNSUPPORTED_INTERFACE         = 10080;
  const SESSION_DECODE_FAIL           = 10085;
  const ACCESS_SYSLOG_FAIL            = 10090;
  const LOAD_RESOURCE_FAIL            = 10095;
  const SVC_SERVER_ERROR              = 10099;
  const SVC_CLIENT_FAIL               = 10100;
  const SVC_CLIENT_BUSY               = 10101;
  const SVC_CLIENT_ERROR              = 10105;
  const SVC_CLIENT_QUERY_ERR          = 10110;
  const DB_CONNECT_FAIL               = 10115;
  const INVALID_MSG_FMT               = 10120;
  const INVALID_HTTP_RESPONSE         = 10130;
  const INVALID_HTTP_REQUEST          = 10135;
  const INVALID_SYNTAX                = 10140;
  const INVALID_TYPE_CAST             = 10145;
  const INVALID_OBJECT_KEY            = 10150;
  const INVALID_OBJECT_PROPERTY_NAME  = 10155;
  const INVALID_OBJECT_DATA_SOURCE    = 10160;
  const INVALID_EXCHANGE_MAPPING      = 10165;
  const INCOMPLETE_INPUT_DATA         = 10170;
  const INVALID_TXFR_CLASS_NAME       = 10175;
  
  
  /**
   * @return Default message for given exception code.
   */
  public static function defaultMessage($code = NULL) {
    
    $message = 'Error triggered in Able Polecat. No error code was given.';
    
    switch ($code) {
      default:
        break;
      case self::UNSUPPORTED_PHP_VER:
        $message = 'PHP version not supported by Able Polecat';
        break;
      case self::BOOT_SEQ_VIOLATION:
        $message = 'Bootstrap procedure sequence violation.';
        break;
      case self::SYS_PATH_ERROR:
        $message = 'Invalid system path error encountered.';
        break;
      case self::BOOT_PATH_INVALID:
        $message = 'Invalid boot file path encountered.';
        break;
      case self::BOOTSTRAP_CLASS_REG:
        $message = 'Invalid loadable class registration.';
        break;
      case self::BOOTSTRAP_LOGGER:
        $message = 'Failed to open default log.';
        break;
      case self::BOOTSTRAP_AGENT:
        $message = 'Failure to initialize application access control agent.';
        break;
      case self::BOOTSTRAP_CONFIG:
        $message = 'Failure to access/set application configuration.';
        break;
      case self::BOOTSTRAP_SESSION:
        $message = 'Failure to start/resume session.';
        break;
      case self::BOOTSTRAP_DB:
        $message = 'Failure to open Able Polecat database.';
        break;
      case self::BOOTSTRAP_BUS:
        $message = 'Failure to bring service bus online.';
        break;
      case self::NO_ENVIRONMENT_CONFIG:
        $message = 'Could not load environment configuration.';
        break;
      case self::MKDIR_FAIL:
        $message = 'Failed attempt to create directory.';
        break;
      case self::ACCESS_INVALID_OBJECT:
        $message = 'Failure to return a environment member object.';
        break;
      case self::LIBS_PATH_INVALID:
        $message = 'Invalid path for contributed class libraries.';
        break;
      case self::MODS_PATH_INVALID:
        $message = 'Invalid path for contributed modules.';
        break;
      case self::LOGS_PATH_INVALID:
        $message = 'Invalid path for log files.';
        break;
      case self::UNSUPPORTED_INTERFACE:
        $message = 'Interface is not supported.';
        break;
      case self::SESSION_DECODE_FAIL:
        $message = 'Failed to decode session.';
        break;
      case self::ACCESS_SYSLOG_FAIL:
        $message = 'Failed to open syslog.';
        break;
      case self::LOAD_RESOURCE_FAIL:
        $message = 'Failed to load contributed resource.';
        break;
      case self::SVC_SERVER_ERROR:
        $message = 'Service internal error.';
        break;
      case self::SVC_CLIENT_FAIL:
        $message = 'Failed to establish client connection to web service.';
        break;
      case self::SVC_CLIENT_BUSY:
        $message = 'Web service client is busy and cannot dispatch request.';
        break;
      case self::SVC_CLIENT_ERROR:
        $message = 'Web service client generated a communications error.';
        break;
      case self::SVC_CLIENT_QUERY_ERR:
        $message = 'Web service client failed to process query.';
        break;
      case self::DB_CONNECT_FAIL:
        $message = 'Database connection failed.';
        break;
      case self::INVALID_MSG_FMT:
        $message = 'Message is improperly formatted.';
        break;
      case self::INVALID_HTTP_RESPONSE:
        $message = 'HTTP response is invalid.';
        break;
      case self::INVALID_HTTP_REQUEST:
        $message = 'HTTP request is invalid.';
        break;
      case self::INVALID_SYNTAX:
        $message = 'Invalid syntax.';
        break;
      case self::INVALID_TYPE_CAST:
        $message = 'Invalid type cast.';
        break;
      case self::INVALID_OBJECT_KEY:
        $message = 'Invalid object key';
        break;
      case self::INVALID_OBJECT_PROPERTY_NAME:
        $message = 'Invalid object property name';
        break;
      case self::INVALID_OBJECT_DATA_SOURCE:
        $message = 'Invalid object data source';
        break;
      case self::INVALID_EXCHANGE_MAPPING:
        $message = 'Invalid exchange mapping';
        break;
      case self::INCOMPLETE_INPUT_DATA:
        $message = 'Incomplete input data';
        break;
      case self::INVALID_TXFR_CLASS_NAME:
        $message = 'Invalid transfer class name';
        break;
    }
    return $message;
  }
}