# Fragment Recall System - Technical Overview

## Current Application State

The Seer application now includes a comprehensive fragment recall system that provides intelligent search, discovery, and analytics capabilities. This document outlines the complete system architecture, implementation details, and operational procedures.

## 🏗️ System Architecture

### Core Components

1. **Fragment Processing Pipeline** (`ProcessFragmentJob`)
2. **Search Infrastructure** (`SearchFragments`, `ParseSearchGrammar`)
3. **Recall Interface** (`ChatInterface` with Ctrl+K palette)
4. **Analytics System** (`LogRecallDecision`, `AnalyzeRecallPatterns`)
5. **Entity Extraction** (`ExtractMetadataEntities`, `GenerateAutoTitle`)

### Database Schema

#### Primary Tables
- **fragments**: Core content storage with FULLTEXT indexes
- **recall_decisions**: User behavior tracking and analytics
- **chat_sessions**: Conversation context and history
- **vaults/projects**: Organizational hierarchy

#### Key Columns Added
```sql
-- fragments table
ALTER TABLE fragments ADD selection_stats JSON NULL; -- Fragment popularity tracking
ALTER TABLE fragments ADD FULLTEXT fulltext_search (title, message); -- MySQL only

-- New recall_decisions table
CREATE TABLE recall_decisions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    query VARCHAR(512),
    parsed_query JSON,
    total_results INT,
    selected_fragment_id BIGINT NULL,
    selected_index INT NULL,
    action VARCHAR(32) DEFAULT 'select',
    context JSON,
    decided_at DATETIME,
    -- Foreign keys and indexes
);
```

## 🔄 Fragment Processing Pipeline

### Pipeline Order
```php
ProcessFragmentJob::class -> [
    ParseChaosFragment::class,     // Split multi-task fragments
    DriftSync::class,              // External sync operations
    ParseAtomicFragment::class,    // Clean and normalize content
    ExtractMetadataEntities::class,// Extract @mentions, emails, URLs, dates
    GenerateAutoTitle::class,      // Intelligent title generation
    EnrichFragmentWithLlama::class,// LLM-based content enrichment
    InferFragmentType::class,      // Automatic type classification
    SuggestTags::class,            // Tag suggestions
    RouteToVault::class,           // Organizational routing
]
```

### Entity Extraction Details

**ExtractMetadataEntities** (`app/Actions/ExtractMetadataEntities.php`) extracts:

- **@mentions**: `@john.doe`, `@team.lead` → `people` array
- **Emails**: `user@company.com` → `emails` array  
- **URLs**: `https://docs.example.com/report.pdf` → `urls` array
- **Dates**: `2024-12-31`, `Friday`, `next week` → `dates` array
- **Phones**: `+1-555-123-4567`, `(555) 123-4567` → `phones` array
- **Code**: `` `git commit -m "fix"` `` → `code_snippets` array

```php
// Example extracted entities
[
    'people' => ['john.doe', 'sarah'],
    'emails' => ['team@company.com'],
    'urls' => ['https://example.com'],
    'dates' => ['2024-12-15', 'Friday'],
    'phones' => ['+1-555-123-4567'],
    'hashtags' => ['urgent', 'client'],
    'references' => ['#123', 'PR-456'],
    'code_snippets' => ['git status', 'npm install']
]
```

### Auto-Title Generation

**GenerateAutoTitle** (`app/Actions/GenerateAutoTitle.php`) uses three strategies:

1. **First Line Strategy**: Extract first line if it's a clear title
   ```
   "Important Meeting Notes\n\nDetailed discussion..." 
   → "Important Meeting Notes"
   ```

2. **First Sentence Strategy**: Use first sentence if under 100 characters
   ```
   "Call client about urgent deadline. Need to prepare slides..."
   → "Call client about urgent deadline"
   ```

3. **Keyword Strategy**: Generate from type, tags, and content
   ```
   type: "task", tags: ["urgent"], content: "client meeting"
   → "Task: client meeting"
   ```

## 🔍 Search System

### Search Grammar

The system supports rich query grammar for advanced filtering:

```
# Basic search
meeting notes

# Type filtering  
type:meeting urgent discussion
type:todo #urgent

# Tag filtering
#urgent #client project update
#meeting -#cancelled

# People filtering
@john.doe project status
@team.lead OR @manager

# Content filtering
has:link documentation
has:code deployment scripts

# Date filtering
before:2024-12-01 project review
after:2024-11-15 meeting notes
today, yesterday, this week, last month

# Session filtering
in:session(abc123) context search

# Combined queries
type:meeting @client #urgent before:2024-12-31
```

### Grammar Parsing

**ParseSearchGrammar** (`app/Actions/ParseSearchGrammar.php`) returns:
```php
[
    'original_query' => 'type:meeting #urgent client notes',
    'search_terms' => 'client notes',        // Clean search terms
    'filters' => [                           // Structured filters
        ['type' => 'type', 'value' => 'meeting'],
        ['type' => 'tag', 'value' => 'urgent']
    ],
    'suggestions' => [...],                  // Auto-complete suggestions
    'autocomplete' => [...],                 // Available filter options
    'valid' => true,
    'errors' => []
]
```

### Hybrid Ranking Algorithm

**SearchFragments** (`app/Actions/SearchFragments.php`) implements hybrid scoring:

```php
// Scoring weights (totals 100%)
final_score = (
    relevance_score * 40 +      // BM25 FULLTEXT relevance
    recency_score * 30 +        // Time-based decay
    tag_match_score * 15 +      // Tag overlap bonus
    session_context_score * 10 + // Session relevance
    selection_popularity * 5     // Historical selection frequency
)
```

**Ranking Components:**
- **BM25 Relevance**: MySQL FULLTEXT scoring for term matching
- **Recency Score**: Exponential decay favoring recent fragments
- **Tag Matching**: Boost for overlapping tags with query context
- **Session Context**: Fragments from current chat session get priority
- **Selection Stats**: Popular fragments rank higher over time

## ⌨️ Recall Interface (Ctrl+K)

### User Experience Flow

1. **Activation**: `Ctrl+K` (global shortcut) opens recall palette
2. **Search**: Type query with live search (2+ characters)
3. **Grammar**: Use advanced filters (`type:`, `#tags`, `@people`)
4. **Navigate**: `↑↓` arrows to select, `Enter` to choose, `Esc` to close
5. **Integration**: Selected fragments appear in chat as context

### Implementation Details

**Location**: `app/Filament/Resources/FragmentResource/Pages/ChatInterface.php`

**Key Properties**:
```php
public bool $showRecallPalette = false;
public string $recallQuery = '';
public array $recallResults = [];
public array $recallSuggestions = [];
public array $recallAutocomplete = [];
public int $selectedRecallIndex = 0;
public bool $recallLoading = false;
```

**Key Methods**:
- `openRecallPalette()`: Initialize palette with suggestions
- `performRecallSearch()`: Execute search with grammar parsing
- `selectRecallResult($index)`: Choose fragment and log decision
- `closeRecallPalette($logDismissal = true)`: Close with optional logging

**Frontend Integration**: 
- Alpine.js for keyboard shortcuts and interactions
- Livewire for server-side search and state management
- Tailwind CSS for styling and responsive design

## 📊 Analytics & Decision Logging

### Decision Logging

Every recall interaction is automatically logged via **LogRecallDecision** (`app/Actions/LogRecallDecision.php`):

```php
// Logged data structure
RecallDecision::create([
    'user_id' => $user->id,
    'query' => 'type:meeting client',           // Original query
    'parsed_query' => [...],                    // Grammar-parsed structure
    'total_results' => 15,                      // Search result count
    'selected_fragment_id' => 123,              // Chosen fragment (or null)
    'selected_index' => 2,                      // Position in results (0-indexed)
    'action' => 'select', // or 'dismiss'       // User action
    'context' => [                              // Rich analytics context
        'click_depth' => 3,                     // 1-indexed position
        'clicked_in_top_n' => [
            'top_1' => false, 'top_3' => true, 'top_5' => true
        ],
        'search_terms' => 'client',
        'filters_used' => ['type'],
        'session_info' => [...]
    ],
    'decided_at' => now()
]);
```

### Analytics Engine

**AnalyzeRecallPatterns** (`app/Actions/AnalyzeRecallPatterns.php`) provides comprehensive insights:

#### Available Analytics
- **Success Rates**: Selection vs dismissal percentages
- **Query Patterns**: Most frequent and successful queries
- **Position Metrics**: Average click position and top-N performance
- **Filter Usage**: Which grammar filters are most effective
- **Time Patterns**: Usage by hour of day and day of week
- **Performance Insights**: Query length stats and response times

#### CLI Analytics Tool
```bash
# Basic analytics for last 30 days
php artisan recall:analyze

# User-specific analytics for last 7 days  
php artisan recall:analyze --user=123 --days=7

# Example output:
📊 RECALL ANALYTICS SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Searches: 156
Successful Selections: 132
Dismissals: 24
Success Rate: 84.6%
Avg Results per Search: 8.3

🎯 SELECTION METRICS
━━━━━━━━━━━━━━━━━━━━━━━━
Average Click Position: 2.1
Top-1 Selections: 45.5%
Top-3 Selections: 78.0%
Top-5 Selections: 91.7%

💡 RECOMMENDATIONS
━━━━━━━━━━━━━━━━━━━━━━━━━━
[MEDIUM] Users typically select results beyond position 3. Consider improving ranking algorithm.
```

### Selection Stats Integration

Fragments track their own selection statistics in `selection_stats` JSON column:

```json
{
  "total_selections": 12,
  "last_selected_at": "2024-08-25T10:30:00Z",
  "search_patterns": {
    "client meeting": 5,
    "project status": 3,
    "urgent": 4
  },
  "filter_patterns": {
    "type": 8,
    "tag": 4
  },
  "position_stats": {
    "total_clicks": 12,
    "average_position": 2.3
  }
}
```

These stats feed back into the ranking algorithm to boost frequently-selected fragments.

## 🧪 Testing Infrastructure

### Test Categories

#### 1. Integration Tests
**FragmentRecallIntegrationTest** (`tests/Feature/FragmentRecallIntegrationTest.php`)
- End-to-end pipeline: Fragment creation → Processing → Search → Recall → Analytics
- Recall palette user interactions and decision logging
- Grammar-based search with various filter combinations
- Selection stats integration with ranking

#### 2. Pipeline Tests  
**FragmentProcessingPipelineTest** (`tests/Feature/FragmentProcessingPipelineTest.php`)
- Individual pipeline action verification
- Entity extraction accuracy across different content types
- Title generation strategies and fallback behavior
- Pipeline order dependencies and error handling

#### 3. Performance Tests
**RecallPerformanceTest** (`tests/Feature/RecallPerformanceTest.php`)
- Large dataset handling (1000+ fragments)
- Concurrent search operations
- Memory usage monitoring
- Database query efficiency validation

#### 4. Unit Tests
**LogRecallDecisionTest** (`tests/Unit/LogRecallDecisionTest.php`)
- Decision logging accuracy and context capture
- Query grammar parsing verification
- Analytics data structure validation

### Running Tests

```bash
# Run all recall system tests
php artisan test --filter=Recall

# Run specific test categories
php artisan test tests/Feature/FragmentRecallIntegrationTest.php
php artisan test tests/Feature/RecallPerformanceTest.php
php artisan test tests/Unit/LogRecallDecisionTest.php

# Run with coverage (if configured)
php artisan test --coverage --filter=Recall
```

### Database Seeding for Testing

```php
// Create test fragments with realistic data
Fragment::factory()->count(100)->create([
    'message' => 'Meeting with @client about project status',
    'type' => 'meeting',
    'tags' => ['urgent', 'client'],
    'parsed_entities' => [
        'people' => ['client'],
        'emails' => ['client@company.com'],
        'dates' => ['2024-12-01']
    ]
]);

// Create recall decisions for analytics testing
RecallDecision::factory()->count(50)->create([
    'query' => 'client meeting',
    'action' => 'select',
    'selected_index' => rand(0, 5)
]);
```

## 🚀 Operational Procedures

### Daily Operations

#### Monitor Search Performance
```bash
# Check recent search success rates
php artisan recall:analyze --days=1

# Monitor failed queries (0 results)
php artisan recall:analyze --days=7 | grep "failed"
```

#### Database Maintenance
```sql
-- Monitor FULLTEXT index usage (MySQL)
SHOW INDEX FROM fragments WHERE Key_name = 'fulltext_search';

-- Check recall_decisions table growth
SELECT COUNT(*), DATE(decided_at) as date 
FROM recall_decisions 
GROUP BY DATE(decided_at) 
ORDER BY date DESC LIMIT 7;

-- Clean old analytics data (optional, after 6 months)
DELETE FROM recall_decisions WHERE decided_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### Performance Tuning

#### Search Optimization
1. **Monitor slow queries**: Enable MySQL slow query log
2. **Index analysis**: Use `EXPLAIN` on search queries
3. **Result limiting**: SearchFragments limits to 100 results by default
4. **Caching**: Consider Redis for frequently accessed fragments

#### Analytics Optimization  
1. **Batch processing**: Analytics computed on-demand, not real-time
2. **Data pruning**: Archive old recall_decisions after analysis
3. **Indexing**: Ensure proper indexes on user_id, decided_at columns

### Debugging Common Issues

#### Search Not Working
```bash
# Check if FULLTEXT index exists (MySQL)
php artisan tinker
> DB::select("SHOW INDEX FROM fragments WHERE Key_name = 'fulltext_search'");

# Test search action directly
> $search = app(\App\Actions\SearchFragments::class);
> $results = $search('test query');
> count($results);
```

#### Recall Palette Issues
```javascript
// Check JavaScript console for errors
// Verify Livewire is loading properly
console.log(window.Livewire);

// Check Alpine.js initialization
console.log(window.Alpine);
```

#### Analytics Not Generating
```php
// Verify decisions are being logged
php artisan tinker
> \App\Models\RecallDecision::count();
> \App\Models\RecallDecision::latest()->first();

// Test analytics directly
> $analyzer = app(\App\Actions\AnalyzeRecallPatterns::class);
> $analysis = $analyzer(auth()->id(), 7);
> $analysis['summary'];
```

## 🔮 Future Enhancement Opportunities

### Immediate Improvements
1. **Semantic Search**: Vector embeddings for content similarity
2. **ML Ranking**: Train models on selection data for better relevance
3. **Smart Suggestions**: Contextual fragment recommendations
4. **Mobile Interface**: Touch-optimized recall palette

### Advanced Features
1. **Real-time Collaboration**: Multi-user recall sessions
2. **Export/Import**: Analytics data portability
3. **API Integration**: External system fragment ingestion
4. **Advanced Analytics**: Cohort analysis, A/B testing framework

### Performance Optimizations
1. **Search Caching**: Redis-backed result caching
2. **Async Processing**: Background analytics computation
3. **Database Sharding**: Horizontal scaling for large datasets
4. **CDN Integration**: Fragment asset caching

## 📋 Configuration Reference

### Environment Variables
```bash
# Database configuration (existing)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=seer
DB_USERNAME=root
DB_PASSWORD=

# Analytics settings (optional)
RECALL_ANALYTICS_RETENTION_DAYS=180
RECALL_MAX_RESULTS=100
RECALL_MIN_QUERY_LENGTH=2
```

### Artisan Commands
```bash
# Analytics and monitoring
php artisan recall:analyze [--user=ID] [--days=N]

# Standard Laravel commands  
php artisan migrate          # Apply database changes
php artisan optimize         # Cache routes, config, etc.
php artisan queue:work       # Process fragment jobs
php artisan schedule:run     # Run scheduled tasks
```

This comprehensive system provides intelligent fragment discovery, continuous learning through user behavior, and detailed analytics for system optimization. The recall system creates a virtuous cycle where user interactions improve search quality over time.