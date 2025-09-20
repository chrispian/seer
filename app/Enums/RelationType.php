<?php

namespace App\Enums;

enum RelationType: string
{
    case Mentions = 'mentions';
    case References = 'references';
    case SimilarTo = 'similar_to';
    case Refines = 'refines';
    case DuplicateOf = 'duplicate_of';
    case ChildOf = 'child_of';
    case ClusterMember = 'cluster_member';

    public function label(): string
    {
        return match ($this) {
            self::Mentions => 'Mentions',
            self::References => 'References',
            self::SimilarTo => 'Similar To',
            self::Refines => 'Refines',
            self::DuplicateOf => 'Duplicate Of',
            self::ChildOf => 'Child Of',
            self::ClusterMember => 'Cluster Member',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Mentions => 'One fragment mentions another',
            self::References => 'One fragment references another',
            self::SimilarTo => 'Fragments are similar in content',
            self::Refines => 'One fragment refines/improves another',
            self::DuplicateOf => 'Fragments are duplicates',
            self::ChildOf => 'One fragment is a child of another',
            self::ClusterMember => 'Fragments belong to the same cluster',
        };
    }
}
