<?php

/*
 * NOTICE: This is a Magento 1.x local.xml generator tool
 * in a pre-alpha stage and was written in a hurry.
 * The code needs lots of refactoring.
 * */


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

    const XPATH_GLOBAL = '//global/';
    const XPATH_ADMIN  = '//admin/';

    const READ_OPT = 'r';
    const EDIT_OPT = 'e';
    const DATE_OPT = 'd';
    const KEY_OPT  = 'k';
    const HELP_OPT = 'h';

    const HELP_DATA = <<<EOF
[Generate or edit local.xml]:

php loxgen.phar [-e] \
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

---------

[Generate install date or encryption key (Edit option can be used)]:

php loxgen.phar \
    -d \
    -k \
    [-e \]
    --mage_root_dir="MAGE_ROOT_DIR"

---------

[Read local.xml]:

php loxgen.phar \
    -r
    --mage_root_dir="MAGE_ROOT_DIR"

---------

[ACTIONS]:

-r: Read
-e: Edit
-d: Generate date
-k: Generate encryption key

(The default action is "Generate" local.xml file)
(There is no need to specify an option for default action)
EOF;


    static private $_localXmlTemplate = '/config/local.xml.template';
    static private $_localXml         = '/app/etc/local.xml';

    static private $_isEdit = false;

    static private $_rootDirOpt = array('mage_root_dir::');

    static private $_data = array();

    static private $_longDataOpts = array(
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

        $actionOpts = self::_getActionOpts();

        switch (true) {
            case $actionOpts[self::HELP_OPT] !== null
                || $action == 'help':
                $result = self::getHelp();
                break;
            case $actionOpts[self::READ_OPT] !== null
                || $action == 'read':
                $result = self::read();
                break;
            case $actionOpts[self::EDIT_OPT] !== null
                || $action == 'edit':
                $result = self::edit();

                if ($actionOpts[self::DATE_OPT] !== null
                    || $actionOpts[self::KEY_OPT] !== null
                    || $action == 'generate_date'
                    || $action == 'generate_key'
                    ) {
                    // Do nothing
                } else {
                    break;
                }
            case $actionOpts[self::DATE_OPT] !== null
                || $action == 'generate_date':
                $result = self::generateDate();

                if ($actionOpts[self::KEY_OPT] === null) {
                    if ($action != 'generate_key') {
                        break;
                    }
                }
            case $actionOpts[self::KEY_OPT] !== null
                || $action == 'generate_key':
                $result = self::generateKey();
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

    public static function edit($data = array(), $echo = false)
    {
        self::_isEdit(true);
        self::_setData($data);

        $data = self::_getData();

        $result  = self::_saveXml($data, true, self::$_opts);
        $message = self::_getResultMessage($result);

        if ($echo) {
            echo $message . "\n";
        }

        return $message;
    }

    public static function read($echo = false)
    {
        $result = self::_readLocalXml();

        if ($echo) {
            echo $result . "\n";
        }

        return $result;
    }

    public static function generateDate($echo = false)
    {
        $data       = self::_getDateData();
        $actionOpts = array('date' => true);

        $result  = self::_saveXml($data, true, $actionOpts);
        $message = self::_getResultMessage($result);

        if ($echo) {
            echo $message . "\n";
        }

        return $message;
    }

    public static function generateKey($echo = false)
    {
        $data       = self::_getEncryptionKeyData();
        $actionOpts = array('key' => true);

        $result  = self::_saveXml($data, true, $actionOpts);
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

    private static function _getActionOpts()
    {
        $opts    = array();
        $actions = array(
            self::HELP_OPT,
            self::READ_OPT,
            self::EDIT_OPT,
            self::DATE_OPT,
            self::KEY_OPT
        );

        foreach ($actions as $action) {
            $opt = getopt($action);

            $opts[$action] = isset($opt[$action])
                ? $opt[$action] : null;
        }

        return $opts;
    }

    private static function _getData()
    {
        self::_setOpts();

        if (self::$_data) {
            return self::$_data;
        }

        $xGlobal = self::XPATH_GLOBAL;
        $xAdmin  = self::XPATH_ADMIN;

        $data = array(
            'db_prefix' => array(
                'xpath' => "{$xGlobal}resources/db/table_prefix",
                'value' => self::_getOpt('db_prefix', '')
            ),
            'db_host' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/host",
                'value' => self::_getOpt('db_host', '')
            ),
            'db_user' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/username",
                'value' => self::_getOpt('db_user', '')
            ),
            'db_pass' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/password",
                'value' => self::_getOpt('db_pass', '')
            ),
            'db_name' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/dbname",
                'value' => self::_getOpt('db_name', '')
            ),
            'db_init_statemants' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/initStatements",
                'value' => self::_getOpt('db_init_statemants', self::DB_INIT_STATEMENT)
            ),
            'db_model' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/model",
                'value' => self::_getOpt('db_model', self::DB_MODEL)
            ),
            'db_type' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/type",
                'value' => self::_getOpt('db_type', self::DB_TYPE)
            ),
            'db_pdo_type' => array(
                'xpath' => "{$xGlobal}resources/default_setup/connection/pdoType",
                'value' => self::_getOpt('db_pdo_type', self::DB_PDO_TYPE)
            ),
            'session_save' => array(
                'xpath' => "{$xGlobal}save_session",
                'value' => self::_getOpt('session_save', self::SESSION_SAVE)
            ),
            'admin_frontname' => array(
                'xpath' => "{$xAdmin}routers/adminhtml/args/frontName",
                'value' => self::_getOpt('admin_frontname', self::ADMIN_FRONTNAME)
            )
        );

        if (!self::$_isEdit) {
            $data = array_merge(
                $data,
                self::_getDateData(),
                self::_getEncryptionKeyData()
            );
        }

        return $data;
    }

    private static function _getDateData()
    {
        $xGlobal = self::XPATH_GLOBAL;

        $data = array(
            'date' => array(
                'xpath' => "{$xGlobal}install/date",
                'value' => self::_getOpt('date', self::_getDate())
            )
        );

        return $data;
    }

    private static function _getEncryptionKeyData()
    {
        $xGlobal = self::XPATH_GLOBAL;

        $data = array(
            'key' => array(
                'xpath' => "{$xGlobal}crypt/key",
                'value' => self::_getOpt('key', self::_getEncryptionKey())
            )
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
        $template = file_get_contents(self::_getLocalXmlTemplatePath());

        foreach ($data as $node => $nodeData) {
            $template = str_replace(
                '{{' . $node . '}}',
                '<![CDATA[' . $nodeData['value'] . ']]>',
                $template
            );
        }

        return $template;
    }

    private static function _isTemplateReadable()
    {
        return is_readable(self::_getLocalXmlTemplatePath());
    }

    private static function _isLocalXmlReadable()
    {
        return is_readable(self::_getLocalXmlPath());
    }

    private static function _isLocalXmlWritable()
    {
        return is_writable(self::_getLocalXmlPath());
    }

    // TODO: Notice that this function together with _getTemplateContents
    // have the same functionality as _saveXml.
    // So, when I have time, I might get rid of them and use _saveXml instead.
    private static function _putLocalXmlContents()
    {
        if ($result = self::_isTemplateReadable()) {
            $template = self::_getTemplateContents();

            $result = file_put_contents(
                self::_getLocalXmlPath(),
                $template
            );
        }

        return $result;
    }

    private static function _saveXml($data, $filterNodes = false, $specificNodes = array())
    {
        $result  = false;
        $canSave = false;

        if (!self::_isLocalXmlWritable()) {
            return $result;
        }

        if ($filterNodes && empty($specificNodes)) {
            return $result;
        }

        try {
            $localXml = self::_getLocalXmlPath();

            $xml = simpleXML_load_file(self::_getLocalXmlPath());

            foreach ($data as $key => $node) {
                if ($filterNodes && !isset($specificNodes[$key])) {
                    continue;
                }

                $canSave = true;

                $xParts = str_split(
                    $node['xpath'],
                    strrpos($node['xpath'], '/')
                );

                $xpath    = $xParts[0];
                $nodeName = ltrim($xParts[1], '/');

                $xpath = $xml->xpath($xpath);

                list($_node) = $xpath;

                $_node->{$nodeName} = NULL;

                $_node = dom_import_simplexml($_node->{$nodeName});

                $nodeOwner = $_node->ownerDocument;

                $_node->appendChild(
                    $nodeOwner->createCDATASection($node['value'])
                );
            }


            if ($canSave) {
                $xmlContent = $xml->asXML();

                $result = file_put_contents($localXml, $xmlContent);
            }
        } catch (Exception $e)
        {}

        return $result;
    }

    private static function _readLocalXml()
    {
        $contents = 'null';

        if (self::_isLocalXmlReadable()) {
            $localXml = self::_getLocalXmlPath();
            $contents = file_get_contents($localXml);
        }

        return $contents;
    }

    private static function _getResultMessage($result)
    {
        $message = $result
            ? 'The requested action was executed successfully'
            : 'Could not execute the requested action';

        return $message;
    }

    private static function _isEdit($status = false)
    {
        self::$_isEdit = $status;

        return self::$_isEdit;
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

    private static function _getLocalXmlTemplatePath()
    {
        return BP . self::$_localXmlTemplate;
    }

    private static function _getLocalXmlPath()
    {
        return self::_getMageRootDir() . self::$_localXml;
    }

    private static function _getMageRootDir()
    {
        $opt = getopt('', self::$_rootDirOpt);

        $dir = isset($opt['mage_root_dir'])
            ? self::_getDir($opt['mage_root_dir'])
            : getcwd();

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
