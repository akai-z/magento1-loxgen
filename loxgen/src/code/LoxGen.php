<?php

define('DS', DIRECTORY_SEPARATOR);
define('BP', dirname(dirname(__FILE__)));


final class LoxGen
{
    const CHARS_LOWER  = 'abcdefghijklmnopqrstuvwxyz';
    const CHARS_UPPER  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_DIGITS = '0123456789';

    const DB_INIT_STATEMENT = 'SET NAMES utf8';
    const DB_MODEL          = 'mysql4';
    const DB_TYPE           = 'pdo_mysql';
    const DB_PDO_TYPE       = '';
    const SESSION_SAVE      = 'file';
    const ADMIN_FRONTNAME   = 'admin';

    const HELP_OPT = 'h';

    const HELP_DATA = <<<EOF
php YOUR_RUN_FILE.php \
    --mage_root_dir="MAGE_ROOT_DIR" \
    --date="DATE" \
    --key="ENCRYPTION_KEY" \
    --db_prefix="DB_TABLE_PREFIX" \
    --db_host="DB_HOST" \
    --db_user="DB_USERNAME" \
    --db_pass="DB_PASSWORD" \
    --db_name="DB_NAME" \
    --db_init_statemants="DB_INIT_STATEMANTS" \
    --db_model="DB_MODEL" \
    --db_type="DB_TYPE" \
    --db_pdo_type="DB_PDO_TYPE" \
    --session_save="SESSION_SAVE" \
    --admin_frontname="ADMIN_FRONTNAME"
EOF;


    static private $_localXmlTemplate = BP . '/config/local.xml.template';
    static private $_localXml         = '/app/etc/local.xml';

    static private $_data = array();

    static private $_longDataOpts = array(
        'mage_root_dir::',
        'date::',
        'key::',
        'db_prefix::',
        'db_host::',
        'db_user::',
        'db_pass::',
        'db_name::',
        'db_init_statemants::',
        'db_model::',
        'db_type::',
        'db_pdo_type::',
        'session_save::',
        'admin_frontname::'
    );

    static private $_opts = array();


    public static function main($action = '', $data = array(), $echo = false)
    {
        $result = '';

        $helpOpt = getopt(self::HELP_OPT);

        switch (true) {
            case isset($helpOpt[self::HELP_OPT]) || $action == 'help':
                $result = self::getHelp();
                break;
            default:
                $result = self::generate($data);
        }

        if ($echo) {
            echo $result . "\n";
        }

        return $result;
    }

    public static function generate($data = array(), $echo = false)
    {
        self::_setData($data);

        $result  = self::_putLocalXmlContents();
        $message = self::_getResultMessage($result);

        if ($echo) {
            echo $message . "\n";
        }

        return $message;
    }

    public static function getHelp($echo = false)
    {
        if ($echo) {
            echo self::HELP_DATA . "\n";
        }

        return self::HELP_DATA;
    }

    private static function _getData()
    {
        self::_setOpts();

        $data = array(
            'date'               => self::_getOpt('date', self::_getDate()),
            'key'                => self::_getOpt('key', self::_getEncryptionKey()),
            'db_prefix'          => self::_getOpt('db_prefix', ''),
            'db_host'            => self::_getOpt('db_host', ''),
            'db_user'            => self::_getOpt('db_user', ''),
            'db_pass'            => self::_getOpt('db_pass', ''),
            'db_name'            => self::_getOpt('db_name', ''),
            'db_init_statemants' => self::_getOpt('db_init_statemants', self::DB_INIT_STATEMENT),
            'db_model'           => self::_getOpt('db_model', self::DB_MODEL),
            'db_type'            => self::_getOpt('db_type', self::DB_TYPE),
            'db_pdo_type'        => self::_getOpt('db_pdo_type', self::DB_PDO_TYPE),
            'session_save'       => self::_getOpt('session_save', self::SESSION_SAVE),
            'admin_frontname'    => self::_getOpt('admin_frontname', self::ADMIN_FRONTNAME)
        );

        return $data;
    }

    private static function _setData($data)
    {
        self::$_data = $data;
    }

    private static function _getTemplateContents()
    {
        $data     = self::_getData();
        $template = file_get_contents(self::$_localXmlTemplate);

        foreach ($data as $index => $value) {
            $template = str_replace(
                '{{' . $index . '}}', '<![CDATA[' . $value . ']]>', $template
            );
        }

        return $template;
    }

    private static function _isTemplateReadable()
    {
        return is_readable(self::$_localXmlTemplate);
    }

    private static function _putLocalXmlContents()
    {
        if ($result = self::_isTemplateReadable()) {
            $template = self::_getTemplateContents();

            $result = file_put_contents(
                self::_getLocalXmlPath(self::$_localXml),
                $template
            );
        }

        return $result;
    }

    private static function _getResultMessage($result)
    {
        $message = $result
            ? 'local.xml file has been generated'
            : 'local.xml file could not be generated';

        return $message;
    }

    private static function _setOpts()
    {
        self::$_opts = getopt('', self::$_longDataOpts);
    }

    private static function _getOpt($opt, $default)
    {
        $option = '';
        $opts   = self::$_opts;

        switch (true) {
            case isset(self::$_data[$opt]):
                $option = self::$_data[$opt];
                break;
            case isset($opts[$opt]):
                $option = $opts[$opt];
                break;
            default:
                $option = $default;
        }

        return $option;
    }

    private static function _getLocalXmlPath()
    {
        return self::_getMageRootDir() . self::$_localXml;
    }

    private static function _getMageRootDir()
    {
        $dir = self::_getDir(
            self::_getOpt('mage_root_dir', getcwd())
        );

        return $dir;
    }

    private static function _getDir($dir)
    {
        return str_replace('/', DS, $dir);
    }

    private static function _getDate()
    {
        return date('r', time());
    }

    private static function _getEncryptionKey()
    {
        $len   = 10;
        $chars = self::CHARS_LOWER . self::CHARS_UPPER . self::CHARS_DIGITS;

        mt_srand(10000000*(double)microtime());

        for ($i = 0, $randStr = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $randStr .= $chars[mt_rand(0, $lc)];
        }

        return md5($randStr);
    }
}
