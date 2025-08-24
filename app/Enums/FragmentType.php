<?php

namespace App\Enums;

enum FragmentType: string
{
    // Core fragment types
    case Fragment = 'fragment';
    case Article = 'article';
    case Note = 'note';
    
    // Structured object types
    case Todo = 'todo';
    case Task = 'task';
    case Meeting = 'meeting';
    case Contact = 'contact';
    case Link = 'link';
    case File = 'file';
    case Media = 'media';
    case CalendarEvent = 'calendar_event';
    case Calendar = 'calendar';
    
    // Capture types
    case Insight = 'insight';
    case Observation = 'observation';
    case Thought = 'thought';
    case Idea = 'idea';
    case Question = 'question';
    case Log = 'log';
    case Shard = 'shard';
    case Seed = 'seed';
    case Bookmark = 'bookmark';
    case Reminder = 'reminder';
    
    // Action types
    case Buy = 'buy';
    case Call = 'call';
    case Check = 'check';
    case Clean = 'clean';
    case Create = 'create';
    case Email = 'email';
    case Find = 'find';
    case Finish = 'finish';
    case Look = 'look';
    case Make = 'make';
    case Order = 'order';
    case Pick = 'pick';
    case Research = 'research';
    case Review = 'review';
    case Schedule = 'schedule';
    case Split = 'split';
    case Start = 'start';
    case Try = 'try';
    case Trying = 'trying';
    case Write = 'write';
    
    // Contextual types
    case Critical = 'critical';
    case Dynamic = 'dynamic';
    case Each = 'each';
    case Error = 'error';
    case How = 'how';
    case Maybe = 'maybe';
    case This = 'this';
    case Time = 'time';
    case What = 'what';

    public function label(): string
    {
        return match($this) {
            // Core fragment types
            self::Fragment => 'Fragment',
            self::Article => 'Article',
            self::Note => 'Note',
            
            // Structured object types
            self::Todo => 'Todo',
            self::Task => 'Task',
            self::Meeting => 'Meeting',
            self::Contact => 'Contact',
            self::Link => 'Link',
            self::File => 'File',
            self::Media => 'Media',
            self::CalendarEvent => 'Calendar Event',
            self::Calendar => 'Calendar',
            
            // Capture types
            self::Insight => 'Insight',
            self::Observation => 'Observation',
            self::Thought => 'Thought',
            self::Idea => 'Idea',
            self::Question => 'Question',
            self::Log => 'Log',
            self::Shard => 'Shard',
            self::Seed => 'Seed',
            self::Bookmark => 'Bookmark',
            self::Reminder => 'Reminder',
            
            // Action types
            self::Buy => 'Buy',
            self::Call => 'Call',
            self::Check => 'Check',
            self::Clean => 'Clean',
            self::Create => 'Create',
            self::Email => 'Email',
            self::Find => 'Find',
            self::Finish => 'Finish',
            self::Look => 'Look',
            self::Make => 'Make',
            self::Order => 'Order',
            self::Pick => 'Pick',
            self::Research => 'Research',
            self::Review => 'Review',
            self::Schedule => 'Schedule',
            self::Split => 'Split',
            self::Start => 'Start',
            self::Try => 'Try',
            self::Trying => 'Trying',
            self::Write => 'Write',
            
            // Contextual types
            self::Critical => 'Critical',
            self::Dynamic => 'Dynamic',
            self::Each => 'Each',
            self::Error => 'Error',
            self::How => 'How',
            self::Maybe => 'Maybe',
            self::This => 'This',
            self::Time => 'Time',
            self::What => 'What',
        };
    }

    public function color(): string
    {
        return match($this) {
            // Core fragment types - neutral colors
            self::Fragment => 'gray',
            self::Article => 'blue',
            self::Note => 'slate',
            
            // Structured object types - distinct colors
            self::Todo, self::Task => 'green',
            self::Meeting => 'purple',
            self::Contact => 'yellow',
            self::Link => 'indigo',
            self::File, self::Media => 'pink',
            self::CalendarEvent, self::Calendar => 'red',
            
            // Capture types - blue family
            self::Insight => 'cyan',
            self::Observation => 'sky',
            self::Thought => 'blue',
            self::Idea => 'violet',
            self::Question => 'indigo',
            self::Log => 'slate',
            self::Shard => 'stone',
            self::Seed => 'emerald',
            self::Bookmark => 'amber',
            self::Reminder => 'orange',
            
            // Action types - warm colors
            self::Buy => 'green',
            self::Call => 'blue',
            self::Check => 'teal',
            self::Clean => 'cyan',
            self::Create => 'violet',
            self::Email => 'blue',
            self::Find => 'indigo',
            self::Finish => 'green',
            self::Look => 'sky',
            self::Make => 'purple',
            self::Order => 'orange',
            self::Pick => 'yellow',
            self::Research => 'cyan',
            self::Review => 'amber',
            self::Schedule => 'red',
            self::Split => 'stone',
            self::Start => 'emerald',
            self::Try, self::Trying => 'orange',
            self::Write => 'purple',
            
            // Contextual types - special colors
            self::Critical => 'red',
            self::Dynamic => 'violet',
            self::Each => 'slate',
            self::Error => 'red',
            self::How => 'cyan',
            self::Maybe => 'yellow',
            self::This => 'stone',
            self::Time => 'orange',
            self::What => 'sky',
        };
    }
}