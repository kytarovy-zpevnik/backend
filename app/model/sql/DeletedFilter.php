<?php
namespace App\Model\Sql;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use App\Model\Entity\Songbook;
use App\Model\Entity\Song;
/**
 * Filters deleted content out.
 *
 * @author Jiří Mantlík
 */
class DeletedFilter extends SQLFilter
{
    /**
     * Gets the SQL query part to add to a query.
     * @param ClassMetaData $targetEntity
     * @param string $targetTableAlias
     * @return string The constraint SQL if there is available, empty string otherwise.
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->reflClass->getName() !== Songbook::class && $targetEntity->reflClass->getName() !== Song::class) {
            return '';
        }
        return $targetTableAlias . '.archived = 0';
    }
}