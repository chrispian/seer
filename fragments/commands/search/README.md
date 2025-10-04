# Search Command (Unified)

## Overview
This unified search command merges the advanced hybrid search capabilities from `SearchCommand.php` with the simple DSL-based search from `search/command.yaml`, providing both basic text search and sophisticated embedding-based search with automatic fallback.

## Features Merged

### From Hardcoded Version
- **Hybrid Search**: Vector embeddings + text search combined
- **Automatic Fallback**: Graceful degradation when embeddings unavailable
- **Advanced Scoring**: Weighted combination of vector similarity and text ranking
- **Rich Results**: Snippet generation with highlighting
- **Context Awareness**: Vault, project, session filtering
- **Performance Optimization**: pgvector support detection

### From YAML Version
- **Simple Interface**: Clean, declarative search workflow
- **Template-Based**: Consistent response formatting
- **DSL Integration**: Leverages command framework capabilities

## Usage

### Basic Search
```
/search machine learning
/s "authentication bug"
/find user interface design
```

### Context-Aware Search
The command automatically uses available context:
- Current vault
- Active project
- Chat session
- User preferences

## Search Modes

### Hybrid Mode (Embeddings Enabled)
1. **Vector Search**: Semantic similarity using embeddings
2. **Text Search**: Full-text search with ranking
3. **Combined Scoring**: Weighted combination (60% text + 40% vector)
4. **Snippet Generation**: Highlighted result excerpts

### Text-Only Mode (Embeddings Disabled)
1. **Full-Text Search**: PostgreSQL text search capabilities
2. **Relevance Ranking**: Based on text similarity
3. **Fragment Matching**: Title and content search
4. **Basic Snippets**: Truncated content preview

### Fallback Behavior
- Starts with hybrid search if embeddings available
- Falls back to text-only if hybrid returns no results
- Provides clear indication of search mode used
- Maintains consistent result format across modes

## Unification Strategy

### Intelligent Mode Selection
The command automatically determines the best search approach:
1. **Configuration Check**: Detects if embeddings are enabled
2. **Capability Detection**: Verifies pgvector extension availability
3. **Graceful Degradation**: Falls back to text search when needed
4. **Transparent Operation**: User doesn't need to choose mode

### Feature Preservation
- All advanced search capabilities maintained
- Simple interface preserved for ease of use
- Performance optimizations retained
- Error handling comprehensive

## Technical Implementation

### DSL Steps Used
- `validate` - Input validation and error handling
- `condition` - Mode selection and fallback logic
- `search.query` - Advanced search capabilities
- `fragment.query` - Basic fragment querying
- `transform` - Result formatting and enhancement
- `response.panel` - Rich search results display

### Advanced Features
- **Hybrid SQL Queries**: Complex embedding + text search
- **Dynamic Snippets**: Context-aware result highlighting
- **Scoring Algorithm**: Weighted relevance calculation
- **Context Integration**: Automatic filter application

### Template Features
- Configuration-aware templating
- Dynamic message generation based on search mode
- Rich fragment data enhancement
- Consistent result formatting

## Search Algorithm

### Hybrid Search Process
```sql
SELECT 
  f.id,
  ts_headline(content, query) AS snippet,
  (1 - (embedding <=> query_vector)) AS vec_sim,
  ts_rank_cd(tsvector, query) AS txt_rank,
  (0.6 * ts_rank + 0.4 * vec_sim) AS score
FROM fragments f
JOIN fragment_embeddings e ON e.fragment_id = f.id
ORDER BY score DESC
```

### Fallback Search
- Uses fragment.query DSL step
- Leverages existing search infrastructure  
- Maintains consistent response format
- Preserves context filtering

## Result Enhancement

### Fragment Data
Each result includes:
- Original fragment data
- Generated snippet with highlighting
- Relevance scores (vector + text)
- Type information
- Metadata preservation

### UI Integration
- Panel-based result display
- Search mode indication
- Query preservation for refinement
- Consistent with other command responses

## Migration Notes
- Maintains all advanced search functionality
- Preserves simple interface for basic users
- Enhances error handling and validation
- Zero functionality regression
- Improved response consistency
- Better integration with command framework

This unification successfully combines sophisticated search technology with simple user experience, demonstrating the power of the DSL framework for complex operations.