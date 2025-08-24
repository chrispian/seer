<?php

namespace App\Enums;

enum FragmentType: string
{
    case Fragment = 'fragment';
    case Article = 'article';
    case Todo = 'todo';
    case Meeting = 'meeting';
    case Contact = 'contact';
    case Link = 'link';
    case File = 'file';
    case CalendarEvent = 'calendar_event';

    public function label(): string
    {
        return match($this) {
            self::Fragment => 'Fragment',
            self::Article => 'Article',
            self::Todo => 'Todo',
            self::Meeting => 'Meeting',
            self::Contact => 'Contact',
            self::Link => 'Link',
            self::File => 'File',
            self::CalendarEvent => 'Calendar Event',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Fragment => 'gray',
            self::Article => 'blue',
            self::Todo => 'green',
            self::Meeting => 'purple',
            self::Contact => 'yellow',
            self::Link => 'indigo',
            self::File => 'pink',
            self::CalendarEvent => 'red',
        };
    }
}