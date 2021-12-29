<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\DateTime;
use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\Html\TableCell;
use Framelix\Framelix\Lang;

use function implode;

/**
 * Storable extended with update/create time and user
 * @property DateTime $createTime
 * @property DateTime $updateTime
 * @property User|null $createUser
 * @property User|null $updateUser
 */
abstract class StorableExtended extends Storable
{
    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['createTime']->databaseType = 'datetime';
        $selfStorableSchema->properties['updateTime']->databaseType = 'datetime';
    }

    /**
     * Get table cell for create/update timestamps
     * @return TableCell|null
     */
    public function getModifiedTimestampTableCell(): ?TableCell
    {
        if (!$this->id) {
            return null;
        }
        $tooltip = [
            Lang::get('__framelix_modified_timestamp_createuser__', [
                $this->createUser->email ?? Lang::get("__framelix_unknown_user__"),
                $this->createTime->format('d.m.Y H:i:s')
            ])
        ];
        $html = '';
        if ($this->createTime->getTimestamp() !== $this->updateTime->getTimestamp()) {
            $html = '<span class="material-icons" title="__framelix_modified_timestamp_updated__">edit</span>';
            $tooltip[] = Lang::get('__framelix_modified_timestamp_updateuser__', [
                $this->updateUser->email ?? Lang::get("__framelix_unknown_user__"),
                $this->updateTime->format('d.m.Y H:i:s')
            ]);
        }
        $html = '<span class="framelix-modified-timestamp"><span title="' . implode(
                "<br/>",
                $tooltip
            ) . '">' . $this->updateTime->format('d.m.Y H:i:s') . '</span>' . $html . '</span>';
        $tableCell = new TableCell();
        $tableCell->sortValue = $this->updateTime->getTimestamp();
        $tableCell->stringValue = $html;
        return $tableCell;
    }

    /**
     * Do not change updateuser and updatetime on store
     */
    public function preserveUpdateUserAndTime(): void
    {
        // assign it that way to prevent editor code suggestion for left equals right side
        $this->updateTime = $this->{'updateTime'};
        $this->updateUser = $this->{'updateUser'};
    }

    /**
     * Clone self without an id and without create/update times
     * @return static
     */
    public function clone(): static
    {
        $obj = new static();
        $obj->connectionId = $this->connectionId;
        foreach (self::getStorableSchema($this)->properties as $propertyName => $property) {
            if ($propertyName === 'id' || $propertyName === 'createTime' || $propertyName === 'createUser' || $propertyName === 'updateTime' || $propertyName === 'updateUser') {
                continue;
            }
            $obj->{$property->name} = $this->{$property->name};
        }
        return $obj;
    }

    /**
     * Store
     */
    public function store(): void
    {
        // set create time and user only when it is a new object
        if (!$this->id) {
            if (!$this->isPropertyModified("createTime")) {
                $this->createTime = new DateTime("now");
            }
            if (!$this->isPropertyModified("updateTime")) {
                $this->updateTime = new DateTime("now");
            }
            if (!$this->isPropertyModified("createUser")) {
                $this->createUser = User::get(true);
            }
            if (!$this->isPropertyModified("updateUser")) {
                $this->updateUser = User::get(true);
            }
        } elseif ($this->propertyCache['modified'] ?? null) {
            // set update user and time only when any property has been modified
            if (!$this->isPropertyModified("updateUser")) {
                $this->updateUser = User::get(true);
            }
            if (!$this->isPropertyModified("updateTime")) {
                $this->updateTime = new DateTime("now");
            }
        }
        parent::store();
    }
}