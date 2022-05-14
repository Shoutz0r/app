<?php

namespace App\Events\Internal;

use App\Events\ReadOnlyEvent;
use App\Models\Album;

/**
 * Class AlbumCreateEvent
 * Gets called when an album gets added to Shoutz0r
 *
 * @package App\Events
 */
class AlbumCreateEvent extends ReadOnlyEvent
{
    public const NAME = 'artist.create';

    protected $album;

    public function __construct(Album $album)
    {
        $this->album = $album;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }
}
