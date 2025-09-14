# Enhanced Question Search with ChatGPT Fallback

## Overview

This document describes the enhanced question search functionality that uses ChatGPT as a fallback when the simple keyword-based search fails to find similar questions.

## Problem

The original `findMostSimilarQuestion` method in `OpenAIService` used a simple keyword-based similarity algorithm that often failed to find similar questions, especially when questions were phrased differently but had the same meaning.

## Solution

Created a new enhanced search system that:

1. **First attempts** simple keyword-based search
2. **If no good match found** (similarity < 0.4), uses ChatGPT to find the most similar question
3. **ChatGPT prompt**: "На какой вопрос из списка вопросов больше всего похож этот вопрос: \"{$question}\"? Вот список вопросов: {questions}. Отвечай только цитатой вопроса, ничего не объясняй."

## Files Created/Modified

### New Files

1. **`app/Services/EnhancedTopicFinderService.php`**
   - Main service for enhanced question matching
   - `findMostSimilarQuestionWithAI()` - uses ChatGPT fallback
   - `findBestTopic()` - enhanced topic finding

2. **`app/Console/Commands/EnhancedFindTopicCommand.php`**
   - Command: `php artisan ai:enhanced-find-topic`
   - Tests enhanced topic finding

3. **`app/Console/Commands/TestEnhancedSearchCommand.php`**
   - Command: `php artisan ai:test-enhanced-search`
   - Compares original vs enhanced search

### Modified Files

1. **`app/Services/OpenAIService.php`**
   - Added `askWithEnhancedSearch()` method
   - Added `generatePromptWithEnhancedSimilarQuestions()` method
   - Added `findEnhancedSimilarQuestions()` method

2. **`app/Http/Controllers/TelegramWebhookController.php`**
   - Updated to use `askWithEnhancedSearch()` instead of `ask()`

3. **`app/Console/Commands/AskAICommand.php`**
   - Updated to use `askWithEnhancedSearch()` instead of `ask()`

## Usage

### Testing Commands

```bash
# Test enhanced search functionality
php artisan ai:test-enhanced-search "Можно ли путешествовать во времени?"

# Test enhanced topic finding
php artisan ai:enhanced-find-topic "Есть ли жизнь на других планетах?"

# Ask AI with enhanced search
php artisan ai:ask "Существуют ли инопланетяне?"
```

### In Code

```php
$openAIService = app(OpenAIService::class);

// Use enhanced search
$response = $openAIService->askWithEnhancedSearch($question);

// Use enhanced topic finder directly
$enhancedTopicFinder = app(EnhancedTopicFinderService::class);
$similarQuestion = $enhancedTopicFinder->findMostSimilarQuestionWithAI($question);
```

## How It Works

1. **Simple Search**: First tries keyword-based similarity search
2. **ChatGPT Fallback**: If similarity < 0.4, asks ChatGPT to find the most similar question
3. **Exact Matching**: Searches for exact match of ChatGPT response in database
4. **Partial Matching**: If no exact match, tries partial string matching
5. **AI Response**: If similar question found, uses its answer; otherwise generates AI response

## Benefits

- **Better Accuracy**: ChatGPT understands semantic similarity better than keyword matching
- **Fallback System**: Graceful degradation from simple to advanced search
- **Maintained Performance**: Only uses ChatGPT when simple search fails
- **Logging**: Comprehensive logging for debugging and monitoring

## Example Results

**Question**: "Можно ли путешествовать во времени?"
**Found Similar**: "Существует ли машина времени?"
**Answer**: "Нет"

This demonstrates how the enhanced search correctly identifies semantically similar questions that the original keyword-based search might miss.
