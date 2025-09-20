# Fragments Engine

**Fragments Engine** is an intelligent knowledge capture and recall system designed for rapid thought logging, AI-augmented reflection, and seamless information discovery. Built for ADHD-friendly, frictionless knowledge management.

## ✨ Core Concept

**Fragments** are atomic units of knowledge - thoughts, tasks, meeting notes, ideas, or any piece of information you want to capture and later recall. The system intelligently processes, enriches, and organizes these fragments, making them instantly discoverable when you need them.

## 🚀 Key Features

### 📝 **Intelligent Fragment Capture**
- **Instant Logging**: Capture thoughts as quickly as they occur
- **Auto-Processing**: Automatic entity extraction (@mentions, emails, dates, URLs)
- **Smart Titles**: AI-generated titles when you don't provide one  
- **Type Detection**: Automatically categorizes notes, tasks, meetings, ideas
- **Tag Suggestions**: Intelligent tagging based on content analysis

### ⚡ **Instant Recall System**
- **Ctrl+K Command Palette**: Global shortcut for instant fragment access
- **Advanced Search Grammar**: `type:meeting #urgent @person has:link before:2024-12-01`
- **Intelligent Ranking**: Hybrid algorithm combining relevance, recency, and popularity
- **Live Search**: Real-time results as you type
- **Contextual Suggestions**: Smart recommendations based on current work

### 🤖 **AI-Powered Intelligence**
- **Entity Extraction**: Automatically identifies people, dates, emails, phone numbers
- **Content Enrichment**: LLM-based analysis and categorization
- **Smart Routing**: Automatically organizes fragments into appropriate vaults/projects
- **Vector Embeddings**: Semantic search with configurable AI providers (OpenAI, Ollama)
- **Learning System**: Gets better at surfacing relevant content over time

### 💬 **Chat-Based Interface**
- **Natural Interaction**: Chat with your knowledge base
- **Slash Commands**: Quick actions via `/recall`, `/frag add`, `/search`
- **Context Integration**: Seamlessly pull fragments into conversations
- **Session Management**: Organized chat history with smart titles

### 📊 **Analytics & Insights**
- **Usage Analytics**: Track search patterns and success rates
- **Performance Insights**: Understand how you interact with your knowledge
- **Continuous Improvement**: System learns from your behavior to improve results

## ⌨️ Interface Overview

### Command Palette (Ctrl+K)
The heart of Fragments Engine - instant access to your entire knowledge base:

```
🔍 Search: meeting with client
📋 Results:
  [Meeting] Client Project Kickoff - 2 days ago
  [Task] Follow up on client feedback - 1 week ago  
  [Note] Client requirements document - 3 weeks ago

💡 Quick Filters:
  type:meeting    Filter by fragment type
  #urgent         Filter by tags
  @john.doe      Filter by people mentions
  has:link       Has attachments or links
```

### Slash Commands
Quick actions within the chat interface:

- `/frag add [content]` - Create a new fragment
- `/recall [query]` - Search and recall fragments  
- `/search [terms]` - Advanced search with grammar
- `/vault [name]` - Switch workspace context
- `/project [name]` - Set project scope

### Search Grammar
Powerful query language for precise fragment discovery:

```bash
# Basic search
meeting notes client discussion

# Type filtering
type:todo urgent tasks
type:meeting #client

# Tag combinations  
#urgent #project -#completed
#meeting OR #call

# People and mentions
@john.doe project updates
@team.lead OR @manager

# Content filters
has:link documentation
has:code deployment scripts
has:attachment presentations

# Date and time
today meeting notes
yesterday #standup
this week project updates
before:2024-12-01 quarterly review
after:2024-11-15 #retrospective

# Advanced combinations
type:meeting @client #urgent before:2024-12-31 has:link
```

## 🏗️ System Architecture

### Fragment Processing Pipeline
Every fragment goes through intelligent processing:

1. **Capture** → Raw input from user
2. **Parse** → Extract structure and meaning
3. **Enrich** → Add metadata, entities, context
4. **Classify** → Determine type and importance  
5. **Route** → Organize into appropriate vault/project
6. **Index** → Make searchable and discoverable

### Intelligent Features

#### Auto-Entity Extraction
```
Input: "Meeting with @john.doe tomorrow. Email slides to team@company.com"

Extracted:
- People: ["john.doe"]  
- Emails: ["team@company.com"]
- Dates: ["tomorrow"]
- Type: "meeting"
- Suggested Tags: ["meeting", "presentation"]
```

#### Smart Title Generation
```
"Call client about urgent project deadline by Friday"
→ Title: "Client Call - Project Deadline"

"Remember to: 1) Update docs 2) Deploy staging 3) Email team"  
→ Title: "Task: Update docs, deploy staging, email team"
```

#### Context-Aware Search
- **Session Context**: Prioritizes fragments from current conversation
- **Vault Scoping**: Searches within relevant workspace  
- **Recent Activity**: Boosts recently accessed or created content
- **Selection Learning**: Learns from your choices to improve ranking

## 🎯 Use Cases

### 📋 **Knowledge Workers**
- Meeting notes with automatic attendee extraction
- Project documentation with smart linking
- Task management with deadline tracking
- Client interaction history

### 🧠 **Researchers & Students** 
- Research note organization with source tracking
- Literature review with citation management
- Idea development with connection mapping
- Study session planning and review

### 💼 **Consultants & Freelancers**
- Client project organization
- Proposal and contract tracking  
- Time and activity logging
- Follow-up management

### 🚀 **Entrepreneurs & Creators**
- Idea capture and development
- Market research organization
- Customer feedback collection
- Product development notes

## 🗺️ Current Roadmap

### ✅ **Recently Completed**
- [x] Advanced search grammar with filtering
- [x] Ctrl+K command palette with live search
- [x] Intelligent fragment processing pipeline
- [x] Auto-entity extraction and title generation
- [x] User behavior analytics and learning system
- [x] Cross-platform database optimization
- [x] Data-driven vault routing system with rule-based fragment routing
- [x] **Semantic Search**: Vector embeddings with configurable toggle and fallback

### 🔄 **In Progress** 
- [ ] UI/UX improvements and modernization
- [ ] Application naming and branding updates
- [ ] Installation and deployment documentation
- [ ] Mobile-responsive interface enhancements

### 🔮 **Upcoming Features**
- [ ] **Smart Connections**: Automatic fragment relationship detection
- [ ] **Export/Sync**: Obsidian, Notion, and Markdown integration
- [ ] **Collaboration**: Shared vaults and team features
- [ ] **Mobile App**: Native iOS/Android applications
- [ ] **API Integration**: External system connectivity
- [ ] **Advanced Analytics**: Usage insights and optimization recommendations
- [ ] **Encryption**: End-to-end encrypted private vaults

### 🎨 **Future Enhancements**
- [ ] **Visual Knowledge Maps**: Interactive fragment relationship graphs
- [ ] **Voice Input**: Audio note capture with transcription
- [ ] **Smart Templates**: Context-aware fragment templates
- [ ] **Workflow Automation**: Trigger-based actions and integrations
- [ ] **Advanced AI**: Custom models trained on your knowledge base
- [ ] **Real-time Collaboration**: Live multi-user editing and discussion

## 🛠️ Technical Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Filament v3 with Livewire 3 and Alpine.js
- **Database**: MySQL with FULLTEXT search optimization
- **Styling**: Tailwind CSS v4
- **Search**: Hybrid ranking with BM25 and machine learning
- **AI Integration**: Local LLM support (Ollama/Llama) + OpenAI compatible
- **Queue System**: Redis-backed job processing
- **Analytics**: Built-in user behavior tracking and insights

## 📈 Performance & Scale

- **Search Speed**: Sub-second results on 10,000+ fragments
- **Concurrent Users**: Optimized for team collaboration
- **Data Storage**: Efficient JSON storage with proper indexing
- **Analytics**: Real-time insights without performance impact
- **Memory Usage**: Optimized for long-running sessions
- **Cross-Platform**: Works on MySQL, SQLite, and PostgreSQL

## ⚙️ Configuration

### Vector Embeddings & Semantic Search

Fragments Engine supports optional vector embeddings for semantic search capabilities. This feature can be toggled on/off and gracefully falls back to text-based search when disabled.

#### Environment Configuration

```bash
# Enable/disable vector embeddings (default: false)
EMBEDDINGS_ENABLED=true

# Embeddings provider (openai, ollama)
EMBEDDINGS_PROVIDER=openai

# OpenAI embedding model
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

# Embeddings version for cache invalidation
EMBEDDINGS_VERSION=1
```

#### Database Requirements

- **PostgreSQL**: Requires `pgvector` extension for vector storage
- **SQLite/MySQL**: Falls back to text-only search automatically

#### Management Commands

```bash
# Backfill embeddings for existing fragments
php artisan embeddings:backfill

# Dry run to see what would be processed
php artisan embeddings:backfill --dry-run

# Process in smaller batches
php artisan embeddings:backfill --batch=50

# Force execution without confirmation
php artisan embeddings:backfill --force

# Use specific provider/model
php artisan embeddings:backfill --provider=ollama --model=nomic-embed-text
```

#### How It Works

When embeddings are **enabled**:
- New fragments automatically generate vector embeddings
- Search uses hybrid scoring (text relevance + semantic similarity)
- Results include similarity scores and enhanced ranking

When embeddings are **disabled**:
- No embedding jobs are queued
- Search falls back to full-text search only
- UI clearly indicates "text-only search" mode
- No performance or functionality degradation

## 🎉 Getting Started

> **Note**: Installation documentation is being updated as part of our upcoming UI modernization. The system is currently fully functional for development and testing.

The Fragments Engine represents a new paradigm in knowledge management - one that adapts to your thinking patterns, learns from your behavior, and evolves to serve your unique cognitive needs. Whether you're capturing fleeting thoughts, managing complex projects, or building a comprehensive knowledge base, Fragments Engine provides the intelligence and flexibility to support your workflow.

---

**Fragments Engine** - *Your thoughts, intelligently organized and instantly accessible.*