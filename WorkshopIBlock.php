<?php

namespace MIT;

namespace MIT\Model;

use function MIT\Function\Junkyard\GUIDv4;
use MIT\Loader;
use MIT\Tool\ILoggerFile;
use \Bitrix\Main\Error;
use \Bitrix\Main\Result;

interface PropertyFilter
{
    static public function Field(string $key): bool;
    static public function Fields( ? array $keys = null) : array;
}

abstract class WorkshopUnity
{
    protected static Result $Result;
    private static array $listDelayFn = [];

    public function getResult(): Result
    {
        return self::$Result;
    }

    protected static function _ErrorGenerator($mes): Result
    {
        self::$Result
            ->addError(new Error($mes));

        return self::$Result;
    }

    protected function _checkError(string $method, string $message = null, bool $checkMod = false): Result
    {
        global $APPLICATION;

        $method = explode('::', $method)[1];

        if ($ex = $APPLICATION->GetException()) {
            self::_ErrorGenerator("[#WU-$method] " . $ex->GetString());
        } elseif (!$checkMod) {
            self::_ErrorGenerator("[#WU-$method] " . ($message ?? 'Неизвестная ошибка'));
        }

        return self::$Result;
    }

    public function checkIsSuccess(?Result $Result = null, bool $exception = true, $reinit = true): void
    {
        $Result ??= self::$Result;

        if (!$Result->isSuccess()) {
            $message = $Result->getErrorMessages();

            if ($reinit) {
                self::InitResult();
            }

            if ($exception) {
                throw new \Exception(implode(PHP_EOL, $message), 1);
            } else
            // echo implode(PHP_EOL, $message) . PHP_EOL;
            {
                $this->Log(implode(PHP_EOL, $message));
            }

        }
    }

    public function checkedChain(Result $Result, $exception = true)
    {
        $this->checkIsSuccess($Result, $exception);
        return $this;
    }

    public static function InitResult()
    {
        self::$Result = new Result();
    }

    public static function registerDelayFn(\Closure $fn): void
    {
        self::$listDelayFn[] = $fn;
    }

    public static function callDelayFn(): void
    {
        foreach (self::$listDelayFn as $DelayFn) {
            $DelayFn();
        }

    }

    private static ILoggerFile $Log;

    public static function mountLogObject(ILoggerFile $Log)
    {
        if (empty(self::$Log)) {
            self::$Log = $Log;
        }

    }

    public function Log(string $message, $l = 0, int $time = 0)
    {
        if (empty(self::$Log)) {
            return $this;
        }

        static $self_time = 0;
        if ($time) {
            $self_time = $time;
        }

        $time = $self_time;
        if ($time) {
            $time = number_format(microtime(true) - $time, 4, '.', '');
        }

        $level = '';
        while ($l--) {
            $level .= "\t";
        }

        $message = "$level$time $message";

        self::$Log->Write($message . PHP_EOL);

        return $this;
    }
}

final class ParamCopyIBlock
{
    public function __construct(
        public ?string $iblockTypeTarget = null,
        public string $prefixName = '',
        public string $suffixName = ' (copy)',
        public string $prefixXmlId = 'c-',
        public string $suffixXmlId = '',
        public string $prefixApiCode = 'c-',
        public string $suffixApiCode = '',
    ) {
    }
}

final class FullDataElement
{
    public $ID;
    public $TIMESTAMP_X;
    public $MODIFIED_BY;
    public $DATE_CREATE;
    public $CREATED_BY;
    public $EXTERNAL_ID;
    public $IBLOCK_ID;
    public $IBLOCK_SECTION_ID;
    public $ACTIVE;
    public $ACTIVE_FROM;
    public $ACTIVE_TO;
    public $SORT;
    public $NAME;
    public $PREVIEW_PICTURE;
    public $PREVIEW_TEXT;
    public $PREVIEW_TEXT_TYPE;
    public $DETAIL_PICTURE;
    public $DETAIL_TEXT;
    public $DETAIL_TEXT_TYPE;
    public $SEARCHABLE_CONTENT;
    public $WF_STATUS_ID;
    public $WF_PARENT_ELEMENT_ID;
    public $WF_NEW;
    public $WF_LOCKED_BY;
    public $WF_DATE_LOCK;
    public $WF_COMMENTS;
    public $WF_LAST_HISTORY_ID;
    public $IN_SECTIONS;
    public $XML_ID;
    public $CODE;
    public $TAGS;
    public $TMP_ID;
    public $SHOW_COUNTER;
    public $SHOW_COUNTER_START;

    public function __construct(...$fields)
    {
        foreach ($fields as $field => $v) {
            $this->{$field} = $v;
        }

    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        } else {
            return null;
        }

    }

    public function __invoke(array $fields = []): array
    {
        $fieldsThis = get_object_vars($this);

        return array_intersect_key(
            $fieldsThis,
            $fields ? array_flip($fields) : $fieldsThis
        );
    }
}

final class CollectorDataElement
{
    public function __construct(
        public int $COUNT,
        public string $NAME,
    ) {
    }
}

final class CarolinaPropertyFilter implements PropertyFilter
{
    public static function Field(string $key): bool
    {
        return self::ManualFields($key);
    }

    public static function Fields( ? array $keys = null) : array
    {
        return self::ManualFields($keys, true);
    }

    public static function ManualFields($key = null, $list = false): mixed
    {
        $filter = [
            'CML2_ARTICLE' => true,
            'TSVET' => true,
            'FORMA_RABOCHEY_CHASTI' => true,
            'DLINA_VYSOTA_RABOCHEY_CHASTI_MM' => true,
            'DIAMETR_RABOCHEY_CHASTI_MM' => true,
            'MAX_SKOROST_VRASHCHENIYA' => true,
            'ZERNISTOST' => true,
            'PAZ' => true,
            'UGLUBLENIE_NA_DUGE' => true,
            'DLINA_KHVOSTOVIKA' => true,
            'OBYEM_STERILIZATSIONNOY_KAMERY' => true,
            'SPETSIFICHESKOE_OBOZNACHENIE_ELASTIKA' => true,
            'KONUSNOST' => true,
            'PROPIS' => true,
            'TIP' => true,
            'TSVETOVAYA_MARKIROVKA' => true,
            'NALICHIE_KRYUCHKA' => true,
            'GARANTIYNYY_SROK' => true,
            'VIDY_NASECHEK_FREZ' => true,
            'VARIANT_ISPOLNENIYA_' => true,
            'RAZMER_DUGI' => true,
            'SHIRINA' => true,
            'RAZMER' => true,
            'DLINA' => true,
            'NOMER_ZUBA' => true,
            'PROIZVODITELNOST' => true,
            'TVERDOST' => true,
            'SECHENIE' => true,
            'MAKSIMALNAYA_OBLAST_ISSLEDOVANIYA' => true,
            'SHIRINA_SHVA' => true,
            'TIP_ASPIRATSII' => true,
            'OBEM' => true,
            'DIAMETR' => true,
            'OBSHCHAYA_DLINA_BORA_MM' => true,
            'CHELYUST' => true,
            'OBYEM' => true,
            'SROK_GODNOSTI' => true,
            'NAZNACHENIE' => true,
            'KLASS_STERILIZATSII' => true,
            'DIAMETR_KHVOSTOVIKA_MM' => true,
            'OBEM_REZERVUARA' => true,
            'VKUS' => true,
            'USLOVNYY_RAZMER_USP' => true,
            'OBEM_VANNY' => true,
            'FASOVKA' => true,
            'VARIANT_TORKA' => true,
            'MYAGKOST' => true,
            'FORMA_DUGI' => true,
            'PODACHA_INSTRUMENTOV' => true,
            'TOLSHCHINA' => true,
            'TIP_UPRAVLENIYA' => true,
            'FORMA' => true,
            'GEOMETRIYA_LEZVIY' => true,
            'SILA_VOZDEYSTVIYA' => true,
            'FASON' => true,
            'TIP_IGLY' => true,
            'KPI' => true,
            'TOLSHCHINA_1' => true,
            'YAVLYAETSYABAZOVOY' => true,
        ];

        if ($list) {
            if (is_null($key)) {
                return array_keys($filter);
            } else {
                return array_keys(array_intersect_key($filter, array_flip($key)));
            }

        } else {
            if (is_null($key)) {
                return $filter;
            } else {
                return ($filter[$key] ?? false);
            }

        }
    }
}

final class WorkshopIBlock extends WorkshopUnity implements IIncludeDependencies
{

    public function __construct(
        private int $iblockIdRoot,
        private int $iblockIdTarget = 0
    ) {
        self::InitResult();
    }

    #region Tool
    public static function Dep(Loader $Loader): bool
    {
        $res = 1;

        return $res;
    }
    #endregion

    #region Incaps
    public function setIBlockIdTarget(int $iblockIdTarget): WorkshopIBlock
    {
        $this->iblockIdTarget = $iblockIdTarget;
        return $this;
    }

    public function getIBlockIdRoot(): int
    {
        return $this->iblockIdRoot;
    }

    public function getIBlockIdTarget(bool $strict = true): int
    {
        if (!$this->iblockIdTarget && $strict) {
            self::_ErrorGenerator('[#WIB-X1] Не определен инфоблок назначения');
            $this->checkIsSuccess();
        }
        return $this->iblockIdTarget;
    }
    #endregion

    #region IBlock
    public function CopyIBlock(ParamCopyIBlock $PSIB): Result
    {
        $iblockIdRoot = $this->getIBlockIdRoot();

        $iblockFields = \CIBlock::GetArrayByID($iblockIdRoot);

        $iblockFields["NAME"] = $PSIB->prefixName . $iblockFields["NAME"] . $PSIB->suffixName;
        $iblockFields["XML_ID"] = $PSIB->prefixXmlId . $iblockFields["XML_ID"] . $PSIB->suffixXmlId;
        $iblockFields["API_CODE"] = !$iblockFields["API_CODE"] ? '' : ($PSIB->prefixApiCode . $iblockFields["API_CODE"] . $PSIB->suffixApiCode);
        $iblockFields["IBLOCK_TYPE_ID"] = $PSIB->iblockTypeTarget ?? $iblockFields["IBLOCK_TYPE_ID"];
        $iblockFields["LIST_PAGE_URL"] = '';
        $iblockFields["SECTION_PAGE_URL"] = '';
        $iblockFields["DETAIL_PAGE_URL"] = '#PRODUCT_URL#?oid=#ID#';
        $iblockFields["CANONICAL_PAGE_URL"] = '';

        unset($iblockFields["ID"], $iblockFields["LID"]);

        $iblockSiteR = \CIBlock::GetSite($iblockIdRoot);
        $iblockGroupPermissionsR = \CIBlock::GetGroupPermissions($iblockIdRoot);

        $iblockFields["GROUP_ID"] = $iblockGroupPermissionsR;
        while ($iblockSite = $iblockSiteR->Fetch()) {
            $iblockFields["LID"][] = $iblockSite['SITE_ID'];
        }

        $iblockTarget = new \CIBlock();
        $iblockIdTarget = (int) $iblockTarget->Add($iblockFields);

        if ($iblockIdTarget === 0) {
            return self::_ErrorGenerator('[#WIB-A00] ' . $iblockTarget->LAST_ERROR);
        } else {
            $this->setIBlockIdTarget($iblockIdTarget);
        }

        return self::$Result;
    }

    private static function _RowsPropertyList(int $propertyIdRoot, int $propertyIdTarget)
    {
        $X = [];
        $Res = (new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\PropertyEnumerationTable::getEntity()))
            ->registerRuntimeField(
                'taret',
                array(
                    'data_type' => \Bitrix\Iblock\PropertyEnumerationTable::class,
                    'reference' => [
                        '=this.XML_ID' => 'ref.XML_ID',
                        'ref.PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?', $propertyIdTarget),
                    ],
                    'join_type' => 'LEFT',
                )
            )

            ->setSelect(['XML_ID', 'VALUE', 'DEF', 'SORT', 'taret.ID'])
            ->setFilter([
                'PROPERTY_ID' => $propertyIdRoot,
                '!taret.XML_ID' => false,
            ])
            ->setOrder(['SORT' => 'ASC'])
            ->exec();

        while ($tempX = $Res->fetch()) {
            $X[$tempX['IBLOCK_PROPERTY_ENUMERATION_taret_ID']] = $tempX;
            unset($X[$tempX['IBLOCK_PROPERTY_ENUMERATION_taret_ID']]['IBLOCK_PROPERTY_ENUMERATION_taret_ID']);
        }

        $Y = (new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\PropertyEnumerationTable::getEntity()))
            ->registerRuntimeField(
                'taret',
                array(
                    'data_type' => \Bitrix\Iblock\PropertyEnumerationTable::class,
                    'reference' => [
                        '=this.XML_ID' => 'ref.XML_ID',
                        'ref.PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?', $propertyIdTarget),
                    ],
                    'join_type' => 'LEFT',
                )
            )

            ->setSelect(['XML_ID', 'VALUE', 'DEF', 'SORT'])
            ->setFilter([
                'PROPERTY_ID' => $propertyIdRoot,
                'taret.XML_ID' => false,
            ])
            ->setOrder(['SORT' => 'ASC'])
            ->exec()
            ->fetchAll();

        return [$X, $Y];
    }

    public function CopyIBlockProperty(?PropertyFilter $Filter = null): Result
    {

        $iblockIdRoot = $this->getIBlockIdRoot();
        $iblockIdTarget = $this->getIBlockIdTarget();

        $q = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\PropertyTable::getEntity());
        $q
            ->registerRuntimeField(
                'taret',
                array(
                    'data_type' => \Bitrix\Iblock\PropertyTable::class,
                    'reference' => [
                        '=this.XML_ID' => 'ref.XML_ID',
                        'ref.IBLOCK_ID' => new \Bitrix\Main\DB\SqlExpression('?', $iblockIdTarget),
                    ],
                    'join_type' => 'LEFT',
                )
            )

            ->setSelect(['*'])
            ->addSelect('taret.XML_ID', 'TARGET__XML_ID')
            ->addSelect('taret.ID', 'TARGET__ID')
            ->setFilter([
                'IBLOCK_ID' => $iblockIdRoot,
                'CODE' => $Filter::Fields(),
            ])
            ->setOrder(['SORT' => 'ASC'])
            #
        ;

        $result = $q->exec();

        while ($property = $result->fetch()) {
            $filterBit = ((int) ($property['PROPERTY_TYPE'] === 'L') << 1) | (int) (!empty($property['TARGET__XML_ID']));
            $iblockPropertyTarget = new \CIBlockProperty();
            $propertyIdTarget = $property['TARGET__ID'] ?? null;
            $propertyIdRoot = $property['ID'];

            $property['IBLOCK_ID'] = $iblockIdTarget;
            $property['ACTIVE'] = 'Y';
            $property['SECTION_PROPERTY'] = 'Y';
            // $property['SMART_FILTER'] = 'Y';
            // $property['DISPLAY_TYPE'] = $property['MULTIPLE'] == 'N' ? 'P' : 'F';
            $property['DISPLAY_TYPE'] = 'F';

            unset($property['ID'], $property['TARGET__XML_ID'], $property['TARGET__ID']);

            if ($filterBit&0b10) {
                $propertyCountRoot = \Bitrix\Iblock\PropertyEnumerationTable::getList([
                    'select' => ['XML_ID', 'VALUE', 'DEF', 'SORT'],
                    'filter' => ['PROPERTY_ID' => $propertyIdRoot],
                    'order' => ['SORT' => 'ASC', 'DEF' => 'ASC'],
                ])->getSelectedRowsCount();
            }

            if ($filterBit === 0b11 && $propertyCountRoot !== count(($RPL = self::_RowsPropertyList($propertyIdRoot, $propertyIdTarget))[0])) {
                $property['VALUES'] = $RPL[0] + $RPL[1];
                $iblockPropertyTarget->UpdateEnum($propertyIdTarget, $property['VALUES'], false);
            }

            if (~$filterBit&0b01) {
                $propertyIdTarget = $iblockPropertyTarget->Add($property);
                $ResultPF = \Bitrix\Iblock\Model\PropertyFeature::setFeatures($propertyIdTarget, [
                    ["FEATURE_ID" => "LIST_PAGE_SHOW", "IS_ENABLED" => "N", "MODULE_ID" => "iblock"],
                    ["FEATURE_ID" => "DETAIL_PAGE_SHOW", "IS_ENABLED" => "Y", "MODULE_ID" => "iblock"],
                    ["FEATURE_ID" => "IN_BASKET", "IS_ENABLED" => "Y", "MODULE_ID" => "catalog"],
                    ["FEATURE_ID" => "OFFER_TREE", "IS_ENABLED" => "Y", "MODULE_ID" => "catalog"],
                ]);
                if (!$ResultPF->isSuccess()) {
                    self::_ErrorGenerator("[#WIB-B03] Ошибка установки параметров свойств " . implode(PHP_EOL, $ResultPF->getErrorMessages()));
                }

            }

            if ($propertyIdTarget === false) {
                return self::_ErrorGenerator('[#WIB-B01] ' . $iblockPropertyTarget->LAST_ERROR);
            }

            $iblockPropertyRoot = new \CIBlockProperty();
            $iblockPropertyRoot->Update($propertyIdRoot, [
                'ACTIVE' => 'Y',
                'SMART_FILTER' => 'N',
                'SECTION_PROPERTY' => 'N',
                'IBLOCK_ID' => $iblockIdRoot,
            ]);
            $ResultPF = \Bitrix\Iblock\Model\PropertyFeature::setFeatures($propertyIdRoot, [
                ["FEATURE_ID" => "LIST_PAGE_SHOW", "IS_ENABLED" => "N", "MODULE_ID" => "iblock"],
                ["FEATURE_ID" => "DETAIL_PAGE_SHOW", "IS_ENABLED" => "N", "MODULE_ID" => "iblock"],
                ["FEATURE_ID" => "IN_BASKET", "IS_ENABLED" => "N", "MODULE_ID" => "catalog"],
            ]);
            if (!$ResultPF->isSuccess()) {
                self::_ErrorGenerator("[#WIB-B03b] Ошибка установки параметров свойств " . implode(PHP_EOL, $ResultPF->getErrorMessages()));
            }

            $property['CODE'] !== 'CML2_ARTICLE'
            && self::registerDelayFn(
                fn() => 1#$iblockPropertyRoot->Update($propertyIdRoot, ['ACTIVE' => 'N'])
            );

            #
        }

        return self::$Result;
    }
    #endregion
}

final class WorkshopIBlockCatalog extends WorkshopUnity
{
    private int $propertyIdSKU = 0;
    private int $iblockIdCatalog = 0;
    private int $iblockIdProduct = 0;

    private bool $mountedByWIB = false;

    public function __construct(
        public WorkshopIBlock $WorkshopIBlock
    ) {
        $this->iblockIdCatalog = $this->WorkshopIBlock->getIBlockIdRoot();
        $this->mountedByWIB = $this->_DefCatalogStatusByWIB();
    }

    #region Incaps
    public function mountedByWIB(): bool
    {
        return $this->mountedByWIB;
    }

    public function getCatalogId(): int
    {
        return $this->iblockIdCatalog;
    }

    public function setProductId(int $iblockIdProduct): WorkshopIBlockCatalog
    {
        if (!$iblockIdProduct) {
            self::_ErrorGenerator('[#WIBC-Y1] Обнуление значения');
            $this->checkIsSuccess();
        }
        $this->iblockIdProduct = $iblockIdProduct;
        return $this;
    }

    public function setProductIdByWIB(): WorkshopIBlockCatalog
    {
        $iblockIdProduct = $this->WorkshopIBlock->getIBlockIdTarget();

        return $this
            ->setProductId($iblockIdProduct);
    }

    public function getProductId(bool $strict = true): int
    {
        if (!$this->iblockIdProduct && $strict) {
            self::_ErrorGenerator('[#WIBC-X1] Не определен инфоблок назначения');
            $this->checkIsSuccess();
        }
        return $this->iblockIdProduct;
    }

    public function checkProductId()
    {
        return (bool) \Bitrix\Catalog\CatalogIblockTable::getList([
            "filter" => [
                "IBLOCK_ID" => $this->getProductId(),
            ],
            "select" => ["*"],
        ])->getSelectedRowsCount();
    }

    public function setSKUId(int $propertyIdSKU): WorkshopIBlockCatalog
    {
        if (!$propertyIdSKU) {
            self::_ErrorGenerator('[#WIBC-Y2] Обнуление значения');
            $this->checkIsSuccess();
        }
        $this->propertyIdSKU = $propertyIdSKU;
        return $this;
    }

    public function getSKUId(bool $strict = true): int
    {
        if (!$this->propertyIdSKU && $strict) {
            self::_ErrorGenerator('[#WIBC-X2] Не определено свойство назначения');
            $this->checkIsSuccess();
        }
        return $this->propertyIdSKU;
    }
    #endregion

    private function _DefCatalogStatusByWIB(): bool
    {
        $iblock = \Bitrix\Catalog\CatalogIblockTable::getList([
            "filter" => [
                "PRODUCT_IBLOCK_ID" => $this->WorkshopIBlock->getIBlockIdRoot(),
            ],
            "select" => ["*"],
        ])->fetch();

        if ($iblock) {
            $this->propertyIdSKU = $iblock['SKU_PROPERTY_ID'];
            $this->iblockIdProduct = $iblock['IBLOCK_ID'];
            $this->WorkshopIBlock->setIBlockIdTarget($this->iblockIdProduct);
        }

        return (bool) $iblock;
    }

    public function MountCatalog(): Result
    {
        global $APPLICATION;

        if ($this->mountedByWIB()) {
            return self::$Result;
        }

        $iblockIdTarget = $this->getProductId();

        if (!$this->checkProductId()) {

            $res = \CCatalog::Add([
                'IBLOCK_ID' => $iblockIdTarget,
            ]);

            if (!$res) {
                if ($ex = $APPLICATION->GetException()) {
                    return self::_ErrorGenerator('[#WIBC-A001] ' . $ex->GetString());
                } else {
                    return self::_ErrorGenerator('[#WIBC-A002] Неизвестная ошибка добавления');
                }

            }
        }

        return self::$Result;
    }

    private static function _AddPropLinkSKU(int $iblockIdTarget, int $iblockIdRoot): int
    {
        $iblockPropertyNew = new \CIBlockProperty();

        $res = (int) $iblockPropertyNew->Add([
            'TIMESTAMP_X' => date('Y-m-d H:i:s'),
            'IBLOCK_ID' => (string) $iblockIdTarget,
            'LINK_IBLOCK_ID' => (string) $iblockIdRoot,
            'NAME' => 'Элемент каталога',
            'ACTIVE' => 'Y',
            'SORT' => '9999',
            'CODE' => 'CML2_LINK',
            'DEFAULT_VALUE' => '',
            'PROPERTY_TYPE' => 'E',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'XML_ID' => 'CML2_LINK',
            'FILE_TYPE' => '',
            'MULTIPLE_CNT' => '5',
            'TMP_ID' => '',
            'WITH_DESCRIPTION' => 'N',
            'SEARCHABLE' => 'N',
            'FILTRABLE' => 'Y',
            'IS_REQUIRED' => 'N',
            'VERSION' => '1',
            'USER_TYPE' => 'SKU',
            'USER_TYPE_SETTINGS' => [
                'VIEW' => 'A',
                'SHOW_ADD' => 'N',
                'MAX_WIDTH' => '0',
                'MIN_HEIGHT' => '24',
                'MAX_HEIGHT' => '1000',
                'BAN_SYM' => ',;',
                'REP_SYM' => ' ',
                'OTHER_REP_SYM' => '',
                'IBLOCK_MESS' => 'N',
            ],
            'HINT' => '',
        ]);

        if (!$res) {
            self::_ErrorGenerator('[#WIBC-B0001] ' . $iblockPropertyNew->LAST_ERROR);
        }

        return $res;
    }

    public function MountSKU(): Result
    {
        global $APPLICATION;

        if ($this->mountedByWIB()) {
            return self::$Result;
        }

        $iblockIdRoot = $this->getCatalogId();
        $iblockIdTarget = $this->getProductId();

        if ($this->checkProductId()) {

            $SKUPropId = self::_AddPropLinkSKU($iblockIdTarget, $iblockIdRoot);

            if (!$SKUPropId) {
                return self::$Result;
            }

            $res = \CCatalog::Update($iblockIdTarget, [
                'PRODUCT_IBLOCK_ID' => $iblockIdRoot,
                'SKU_PROPERTY_ID' => $SKUPropId,
            ]);

            if (!$res) {
                if ($ex = $APPLICATION->GetException()) {
                    return self::_ErrorGenerator('[#WIBC-B002] ' . $ex->GetString());
                } else {
                    return self::_ErrorGenerator('[#WIBC-B003] Неизвестная ошибка добавления');
                }

            } else {
                $this->setSKUId($SKUPropId);
            }

        } else {
            return self::_ErrorGenerator("[#WIBC-B001] Инфоблок #" . $iblockIdTarget . " не является торговым каталогом");
        }

        return self::$Result;
    }
}

final class WorkshopIBlockElementCollector
{
    private int $iblockIdRoot;
    private int $iblockIdTarget;

    public function __construct(
        public WorkshopIBlock $WorkshopIBlock
    ) {
        $this->iblockIdTarget = $this->WorkshopIBlock->getIBlockIdTarget();
        $this->iblockIdRoot = $this->WorkshopIBlock->getIBlockIdRoot();
    }

    public function getIBlockIdRoot()
    {
        return $this->iblockIdRoot;
    }

    public function CollectorDataInfo(): array
    {
        $Result = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['COUNT'],
            'filter' => ['IBLOCK_ID' => $this->getIBlockIdRoot(), '>COUNT' => 1],
            'group' => ['NAME'],
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(*)'),
            ),
        ]);

        $i = 0;
        $c = 0;
        while ($row = $Result->fetch()) {
            $c += (int) $row['COUNT'];
            $i++;
        }

        return [$i, $c];
    }

    public function CollectorData(): \Generator
    {
        static $CollectorDataElement = null;

        !$CollectorDataElement
        && $Result = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['NAME', 'COUNT'],
            'filter' => ['IBLOCK_ID' => $this->getIBlockIdRoot(), '>COUNT' => 1],
            'group' => ['NAME'],
            'runtime' => array(
                new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(*)'),
            ),
        ]);

        do {
            if ($row = $Result->fetch()) {
                yield ($CollectorDataElement = new CollectorDataElement(...$row));
            } else {
                return ($CollectorDataElement = null);
            }

        } while ($row);
    }

    public function FullData(string $elementName): \Generator
    {
        static $FullDataElement = null;

        !$FullDataElement
        && $Result = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['*'],
            'filter' => ['IBLOCK_ID' => $this->getIBlockIdRoot(), 'NAME' => $elementName],
        ]);

        do {
            if ($row = $Result->fetch()) {
                yield ($FullDataElement = new FullDataElement(...$row));
            } else {
                return ($FullDataElement = null);
            }

        } while ($row);
    }
}

final class WorkshopIBlockElement extends WorkshopUnity
{
    private int $iblockIdRoot;
    private int $iblockIdTarget;
    private FullDataElement $FullDataElementTarget;
    private FullDataElement $FullDataElementMain;
    private bool $elementNew = true;

    public ?array $storageProductMain = null;

    public function __construct(
        private WorkshopIBlock $WorkshopIBlock,
        private FullDataElement $FullDataElementRoot
    ) {
        self::InitResult();
        $this->iblockIdRoot = $this->WorkshopIBlock->getIBlockIdRoot();
        $this->iblockIdTarget = $this->WorkshopIBlock->getIBlockIdTarget();
    }

    #region Incaps
    private function _PrepareVarExtractElementId() : array
    {
        return [
            'elementIdRoot' => $this
                ->getFullDataElementRoot()
                ->ID,
            'elementIdTarget' => $this
                ->getFullDataElementTarget()
                ->ID,
            'elementIdMain' => $this
                ->getFullDataElementMain()
                ->ID,
        ];
    }

    public function getIBlockIdRoot(): int
    {
        return $this->iblockIdRoot;
    }

    public function getIBlockIdTarget(): int
    {
        return $this->iblockIdTarget;
    }

    public function getFullDataElementRoot(): FullDataElement
    {
        return $this->FullDataElementRoot;
    }

    public function getFullDataElementMain(): FullDataElement
    {
        return $this->FullDataElementMain;
    }

    public function setFullDataElementMain(FullDataElement $FullDataElement): WorkshopIBlockElement
    {
        $this->FullDataElementMain = $FullDataElement;
        return $this;
    }

    public function getFullDataElementTarget(): FullDataElement
    {
        return $this->FullDataElementTarget;
    }

    public function setFullDataElementTarget(FullDataElement $FullDataElement): WorkshopIBlockElement
    {
        $this->FullDataElementTarget = $FullDataElement;
        return $this;
    }

    public function StateElementTarget()
    {
        return $this->elementNew;
    }

    public function markElementExists()
    {
        $this->elementNew = false;
    }

    public function getStoragedProductMain()
    {
        $storageProductMain = $this->storageProductMain;
        $this->storageProductMain = null;

        return $storageProductMain;
    }
    #endregion

    public function CheckChangeElement($time = 0, bool $flagRootMain = false): WorkshopIBlockElement
    {

        $iblockIdTarget = $this->WorkshopIBlock->getIBlockIdTarget();
        $elementIdRoot = $this->getFullDataElementRoot()->ID;

        $fn_ginger = function ($filter, $reg) use ($elementIdRoot) {
            $x = ('\\Bitrix\\Catalog\\' . $reg[0])::getList([
                'select' => $reg[1],
                'filter' => $filter,
            ]);

            $res = $x->fetchAll();

            $xq = md5(var_export($res, true));
            return $xq;
        };

        $fieldPrice = ['EXTRA_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'QUANTITY_FROM', 'QUANTITY_TO'];
        $fieldProduct = ['AVAILABLE', 'VAT_ID', 'VAT_INCLUDED', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'SUBSCRIBE', 'BUNDLE', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE', 'BARCODE_MULTI', 'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER'];

        $rowRoot = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'XML_ID', 'TIMESTAMP_X', 'ACTIVE'],
            'filter' => ['ID' => $elementIdRoot],
        ])->fetch();

        if ($rowTarget = $this->CheckMountElement($iblockIdTarget, $rowRoot['XML_ID'])) {

            if ($rowRoot['TIMESTAMP_X']->getTimestamp() <= $time) {
                if ($flagRootMain || $rowRoot['ACTIVE'] === 'N') {
                    throw new \Exception("Element [R] #$elementIdRoot not changed by timestamp", -3);
                } else {
                    throw new \Exception("Element [Rd] #$elementIdRoot not changed by timestamp", -2);
                }

            }

            $elementIdTarget = $rowTarget['ID'];

            $hashRoot = ''
            . $fn_ginger(['PRODUCT_ID' => $elementIdRoot], ['PriceTable', $fieldPrice])
            . $fn_ginger(['ID' => $elementIdRoot], ['ProductTable', $fieldProduct])
            #
            ;
            $hashTarget = ''
            . $fn_ginger(['PRODUCT_ID' => $elementIdTarget], ['PriceTable', $fieldPrice])
            . $fn_ginger(['ID' => $elementIdTarget], ['ProductTable', $fieldProduct])
            #
            ;

            if ($hashRoot === $hashTarget) {
                throw new \Exception("Element [T] #$elementIdTarget not changed by signature", -2);
            }
        }

        return $this;
    }

    public function checkMountElement(int $iblockIdTarget, string $elementXMLRoot): array
    {
        static $self_rowTargetMount = null;

        if (($self_rowTargetMount['ID'] ?? false) === $iblockIdTarget) {
            return $self_rowTargetMount;
        }

        $rowTargetMount = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'XML_ID', 'TIMESTAMP_X'],
            'filter' => ['IBLOCK_ID' => $iblockIdTarget, 'XML_ID' => '%#' . $elementXMLRoot],
            'limit' => 1,
        ])->fetch();

        $self_rowTargetMount = $rowTargetMount ? $rowTargetMount : [];

        empty($rowTargetMount)
        && $this->markElementExists();

        return $self_rowTargetMount;
    }

    private static function _copyPic(int $picId): array
    {
        if (!empty($picId) && ($file = \CIBlock::makeFileArray($picId, false, null, array('allow_file_id' => true)))) {
            return (['COPY_FILE' => 'Y'] + $file);
        }

    }

    private static function _giveXmlId(?FullDataElement $FullDataElementMain, FullDataElement $FullDataElementRoot, $heir): string
    {
        if ($heir) {
            if ($FullDataElementMain) {
                $XML_ID = $FullDataElementMain->XML_ID;
            }

            $XML_ID = ($XML_ID ?? $FullDataElementRoot->XML_ID) . '#' . $FullDataElementRoot->XML_ID;
        } else {
            $XML_ID = GUIDv4();
        }

        return $XML_ID;
    }

    private static function _CanonocalValuePropLine(array $FL, array &$linked): void
    {
        $q = new \Bitrix\Main\Entity\Query(\Bitrix\Iblock\PropertyEnumerationTable::getEntity());
        $q
            ->registerRuntimeField(
                'taret',
                array(
                    'data_type' => \Bitrix\Iblock\PropertyTable::class,
                    'reference' => [
                        '=this.PROPERTY_ID' => 'ref.ID',
                        'ref.IBLOCK_ID' => new \Bitrix\Main\DB\SqlExpression('?', $FL['IBLOCK_ID']),
                        'ref.CODE' => new \Bitrix\Main\DB\SqlExpression('?', $FL['CODE']),
                    ],
                    'join_type' => 'INNER',
                )
            )

            ->setSelect(['*'])
            ->setFilter([
                'XML_ID' => $FL['XML_ID'],
            ])
            #
        ;

        $Result = $q->exec();

        while ($row = $Result->fetch()) {
            $linked[$row['XML_ID']] = $row['ID'];
        }

    }

    private static function _RepackProp(array $prop, int $iblockIdTarget): array
    {
        if (empty($prop['VALUE'])) {
            return [];
        }

        $linked = [];
        $clean_prop = array_intersect_key($prop, [
            'VALUE' => null,
            'DESCRIPTION' => null,
            ...(isset($prop['VALUE_XML_ID'])
                ? ['VALUE_XML_ID' => null]
                : [])
        ]);

        if ($prop['MULTIPLE'] === 'N') {
            foreach ($clean_prop as $k => $v) {
                $clean_prop[$k] = [$v];
            }
        }

        $PACK = array_map(function ($arr) use ($clean_prop, $prop, &$linked) {
            $el_PACK = array_combine(array_keys($clean_prop), $arr);

            switch ($prop['PROPERTY_TYPE']) {
                case 'F':
                    if ($el_PACK['VALUE']) {
                        $el_PACK['VALUE'] = self::_copyPic($el_PACK['VALUE']);
                    }

                    break;
                case 'L':
                    $el_PACK['VALUE_XML_ID']
                    && $linked[$el_PACK['VALUE_XML_ID']] =  &$el_PACK['VALUE'];
                    break;
            }

            unset($el_PACK['VALUE_XML_ID']);

            return $el_PACK;
        }, array_map(null, ...array_values($clean_prop)));

        $prop['PROPERTY_TYPE'] === 'L'
        && self::_CanonocalValuePropLine([
            "IBLOCK_ID" => $iblockIdTarget,
            "CODE" => $prop['CODE'],
            'XML_ID' => array_keys($linked),
        ], $linked);

        return [$prop['CODE'] => $PACK];
    }

    private static function _NeedProp(int $iblockIdTarget)
    {
        static $self_iblockIdTarget = 0;
        static $self_propertyNeed = [];

        if ($iblockIdTarget === $self_iblockIdTarget) {
            return $self_propertyNeed;
        }

        $propRes = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('CODE'),
            'filter' => array('IBLOCK_ID' => $iblockIdTarget),
            'order' => array('SORT' => 'ASC'),
        ));
        $propertyNeed = array_map(fn($v) => $v['CODE'], $propRes->fetchAll());

        $self_propertyNeed = $propertyNeed;
        $self_iblockIdTarget = $iblockIdTarget;

        return $propertyNeed;
    }

    public function CopyElement(?FullDataElement $FullDataElementMain = null, $heir = false): Result
    {
        $FullDataElementRoot = $this->getFullDataElementRoot();

        if ($FullDataElementMain) {
            $this->setFullDataElementMain($FullDataElementMain);
        } else {
            $this->setFullDataElementMain($FullDataElementRoot);
            $this->storageProductMain = WorkshopIBlockProduct::GetProductMain($FullDataElementRoot->ID);
        }

        $iblockIdTarget = $this->getIBlockIdTarget();

        $FullDataElementTarget = clone $FullDataElementRoot;

        $FullDataElementTarget->CODE = md5($FullDataElementRoot->ID . $FullDataElementRoot->CODE . GUIDv4());
        $FullDataElementTarget->IBLOCK_ID = $iblockIdTarget;
        $FullDataElementTarget->IBLOCK_SECTION_ID = 0;
        $FullDataElementTarget->XML_ID = $FullDataElementTarget->EXTERNAL_ID = self::_giveXmlId($FullDataElementMain, $FullDataElementRoot, $heir);
        $FullDataElementTarget->ACTIVE = 'N';

        unset(
            $FullDataElementTarget->ID,
            $FullDataElementTarget->TMP_ID,
            $FullDataElementTarget->WF_LAST_HISTORY_ID,
            $FullDataElementTarget->PREVIEW_PICTURE,
            $FullDataElementTarget->DETAIL_PICTURE
        );

        $elementTarget = new \CIBlockElement();

        if ($elementIdTargetMount = ($this->checkMountElement($iblockIdTarget, $FullDataElementRoot->XML_ID)['ID'] ?? 0)) {
            $elementTarget->Update($elementIdTargetMount, $FullDataElementTarget());
            $FullDataElementTarget->ID = $elementIdTargetMount;
        } else {
            $FullDataElementTarget->ID = (int) $elementTarget->Add($FullDataElementTarget());
        }

        if (!$FullDataElementTarget->ID) {
            return self::_ErrorGenerator('[#WIBE-A001] Ошибка при копировании элемента: ' . $elementTarget->LAST_ERROR);
        } else {
            $this->setFullDataElementTarget($FullDataElementTarget);
        }

        foreach (($fieldsPic = ['PREVIEW_PICTURE', 'DETAIL_PICTURE']) as $field) {
            if ($FullDataElementRoot->{$field}) {
                $FullDataElementTarget->{$field} = self::_copyPic($FullDataElementRoot->{$field});
            }

        }

        if ($FullDataElementTarget($fieldsPic)) {
            $elementTarget->Update($FullDataElementTarget->ID, $FullDataElementTarget($fieldsPic));
        }

        return self::$Result;
    }

    public function CopyElementProperty(): Result
    {
        global $APPLICATION;

        $iblockIdRoot = $this->getIBlockIdRoot();
        $iblockIdTarget = $this->getIBlockIdTarget();

        $FullDataElementTarget = $this->getFullDataElementTarget();
        $elementIdRoot = $this->getFullDataElementRoot()->ID;
        $elementIdTarget = $FullDataElementTarget->ID;

        $list_elementPropsRoot = [$elementIdRoot => []];
        \CIBlockElement::GetPropertyValuesArray($list_elementPropsRoot, $iblockIdRoot, ['ID' => $elementIdRoot], ['CODE' => self::_NeedProp($iblockIdTarget)], ['GET_RAW_DATA' => 'Y']);

        $elementPropsRoot = $list_elementPropsRoot[$elementIdRoot];

        if (empty($elementPropsRoot)) {
            return self::_ErrorGenerator('[#WIBE-B002] Свойства элемента ' . $elementIdRoot . ' не найдены');
        }

        $FullDataElementTarget->PROPERTIES = [];
        foreach ($elementPropsRoot as $propRoot) {
            $FullDataElementTarget->PROPERTIES += self::_RepackProp($propRoot, $iblockIdTarget);
        }

        \CIBlockElement::SetPropertyValuesEx($elementIdTarget, false, $FullDataElementTarget->PROPERTIES, ['NewElement' => 'Y']);

        if ($ex = $APPLICATION->GetException()) {
            return self::_ErrorGenerator('[#WIBE-B001] во время обновления ствойств произошла ошибка' . $ex->GetString());
        }

        return self::$Result;
    }

    public function MountProductSKU(): Result
    {
        extract($this->_PrepareVarExtractElementId());

        \CIBlockElement::SetPropertyValuesEx($elementIdTarget, false, [
            'CML2_LINK' => $elementIdMain,
        ], ['NewElement' => 'N']);

        return $this->_checkError(__METHOD__, "Товар #$elementIdTarget не установлен как SKU", true);
    }

    public function changeActivity(int $elementId, string $active): void
    {
        (new \CIBlockElement )
            ->Update($elementId, ["ACTIVE" => $active]);
    }

    public function Disable(int $elementID): Result
    {
        $this->changeActivity($elementID, 'N');
        return $this->_checkError(__METHOD__, 'Ошибка деактивации', true);
    }

    public function Enable(int $elementID): Result
    {
        $this->changeActivity($elementID, 'Y');
        return $this->_checkError(__METHOD__, 'Ошибка активации', true);
    }
}

final class WorkshopIBlockProduct extends WorkshopUnity
{

    public function __construct(
        public WorkshopIBlockElement $WorkshopIBlockElement
    ) {
    }

    private function _PrepareVarExtractElementId(): array
    {
        return [
            'elementIdRoot' => $this
                ->WorkshopIBlockElement
                ->getFullDataElementRoot()
                ->ID,
            'elementIdTarget' => $this
                ->WorkshopIBlockElement
                ->getFullDataElementTarget()
                ->ID,
        ];
    }

    public function CopyProductStore(): Result
    {
        extract($this->_PrepareVarExtractElementId());

        $field = ['STORE_ID', 'AMOUNT'];
        $CDB = \CCatalogStoreProduct::GetList([], ['PRODUCT_ID' => $elementIdRoot], false, false, $field);

        while ($row = $CDB->Fetch()) {
            $res = \CCatalogStoreProduct::Add([
                'PRODUCT_ID' => $elementIdTarget,
                ...array_intersect_key($row, array_flip($field)),
            ]);
        }

        if (isset($res) && !$res) {
            $this->_checkError(__METHOD__, 'Информация по складам для продукта #' . $elementIdTarget . ' не была добавлена');
        }

        return self::$Result;
    }

    public function CopyProductPrice(): Result
    {
        extract($this->_PrepareVarExtractElementId());

        $field = ['ID', 'EXTRA_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'QUANTITY_FROM', 'QUANTITY_TO'];

        $QResultRoot = \Bitrix\Catalog\PriceTable::getList([
            'select' => $field,
            'filter' => ['PRODUCT_ID' => $elementIdRoot],
        ]);

        $addPrice = function ($price) use ($elementIdTarget) {
            unset($price['ID']);
            $AddResult = \Bitrix\Catalog\Model\Price::add([
                'PRODUCT_ID' => $elementIdTarget,
                ...$price,
            ]);

            if (!$AddResult->isSuccess()) {
                WorkshopUnity::_ErrorGenerator("[#WIBE-P006] Ошибка при добавлении цены продукта #$elementIdTarget " . implode(PHP_EOL, $AddResult->getErrorMessages()));
            }

        };

        $updatePrice = function ($priceRoot, $priceTarget) use ($elementIdTarget) {
            $UpdateResult = \Bitrix\Catalog\Model\Price::update($priceTarget['ID'], [
                'PRODUCT_ID' => $priceTarget['PRODUCT_ID'],
                ...$priceRoot,
            ]);

            if (!$UpdateResult->isSuccess()) {
                WorkshopUnity::_ErrorGenerator("[#WIBE-P006] Ошибка при обновлении цены продукта #$elementIdTarget " . implode(PHP_EOL, $UpdateResult->getErrorMessages()));
            }

        };

        $deletePrice = function ($price) use ($elementIdTarget) {
            $DeleteResult = \Bitrix\Catalog\Model\Price::delete($price['ID']);

            if (!$DeleteResult->isSuccess()) {
                WorkshopUnity::_ErrorGenerator("[#WIBE-P006] Ошибка при удалении цены продукта #$elementIdTarget " . implode(PHP_EOL, $DeleteResult->getErrorMessages()));
            }

        };

        if ($this->WorkshopIBlockElement->StateElementTarget()) {
            $QResultTarget = \Bitrix\Catalog\PriceTable::getList([
                'select' => ['ID', 'PRODUCT_ID'],
                'filter' => ['PRODUCT_ID' => $elementIdTarget],
            ]);

            $list_priceRoot = $QResultRoot->fetchAll();
            $list_priceTarget = $QResultTarget->fetchAll();

            $priceCountRoot = count($list_priceRoot);
            $priceCountTarget = count($list_priceTarget);

            $diff = $priceCountRoot - $priceCountTarget;

            if ($priceCountTarget === 0) {
                foreach ($list_priceRoot as $priceRoot) {
                    $addPrice($priceRoot);
                }
            } elseif ($diff === 0) {
                foreach ($list_priceRoot as $k => $priceRoot) {
                    $updatePrice($priceRoot, $list_priceTarget[$k]);
                }
            } elseif ($diff > 0) {
                while ($priceCountTarget--) {
                    $updatePrice(array_pop($list_priceRoot), $list_priceTarget[$priceCountTarget]);
                }

                foreach ($list_priceRoot as $priceRoot) {
                    $addPrice($priceRoot);
                }

            } else {
                while ($priceCountRoot--) {
                    $updatePrice($list_priceRoot[$priceCountRoot], array_pop($list_priceTarget));
                }

                foreach ($list_priceTarget as $priceTarget) {
                    $deletePrice($priceTarget);
                }

            }
        } else {
            while ($priceRoot = $QResultRoot->fetch()) {
                $addPrice($priceRoot);
            }
        }

        return self::$Result;
    }

    public static function GetProductMain(int $ID, &$field = []): array
    {
        if (empty($field)) {
            $field = ['AVAILABLE', 'VAT_ID', 'VAT_INCLUDED', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'SUBSCRIBE', 'BUNDLE', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE', 'BARCODE_MULTI', 'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER'];
        }

        $CDB = \CCatalogProduct::GetList([], ['ID' => $ID], false, false, $field);
        return $CDB->Fetch();
    }

    public function CopyProductMain(): Result
    {
        extract($this->_PrepareVarExtractElementId());

        $field = ['AVAILABLE', 'VAT_ID', 'VAT_INCLUDED', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'SUBSCRIBE', 'BUNDLE', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE', 'BARCODE_MULTI', 'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER'];
        /*
        $CDB = \CCatalogProduct::GetList([], ['ID' => $elementIdRoot], false, false, $field);
        $row = $CDB->Fetch();
         */

        $row = $this->WorkshopIBlockElement->getStoragedProductMain() ?? self::GetProductMain($elementIdRoot, $field);

        if (\Bitrix\Catalog\Model\Product::getCacheItem($elementIdTarget, true)) {
            $res = \Bitrix\Catalog\Model\Product::update(
                $elementIdTarget,
                [
                    ...array_intersect_key($row, array_flip($field)),
                    'QUANTITY_TRACE' => 'D',
                    'CAN_BUY_ZERO' => 'N',
                ]
            );
        } else {
            $res = \Bitrix\Catalog\Model\Product::add([
                'ID' => $elementIdTarget,
                ...array_intersect_key($row, array_flip($field)),
            ]);
        }

        if (isset($res) && !$res) {
            $this->_checkError(__METHOD__, 'Информация по продукту для продукта #' . $elementIdTarget . ' не была добавлена');
        }

        return self::$Result;
    }
}
