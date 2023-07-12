<?php

namespace Codyas\Audit\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\ConversionException;

final class CompressedJsonType extends JsonType
{
    const NAME = 'compressed_json';

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null !== $value) {
            $converted = gzuncompress($value);
            if (false === $converted) {
                throw ConversionException::conversionFailed($value, self::NAME);
            }
        } else {
            $converted = null;
        }
        return parent::convertToPHPValue($converted, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        $converted = gzcompress(parent::convertToDatabaseValue($value, $platform));
        if (FALSE === $converted) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
        return $converted;
    }
}