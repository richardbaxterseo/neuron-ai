<?php
/**
 * Context Extractor utility class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Utils
 */

namespace NeuronAI\Utils;

/**
 * Context Extractor class.
 *
 * Extracts context from surrounding blocks and page metadata.
 */
class ContextExtractor {

    /**
     * Extract context from blocks.
     *
     * @since    1.0.0
     * @param    array    $blocks          Array of blocks.
     * @param    int      $current_block   Current block index (optional).
     * @param    int      $context_range   Number of blocks to include before and after (optional).
     * @return   string                    Extracted context.
     */
    public static function extractFromBlocks($blocks, $current_block = null, $context_range = 3) {
        if (empty($blocks)) {
            return '';
        }

        // If no current block specified, use all blocks
        if ($current_block === null) {
            $start = 0;
            $end = count($blocks) - 1;
        } else {
            // Calculate range of blocks to include
            $start = max(0, $current_block - $context_range);
            $end = min(count($blocks) - 1, $current_block + $context_range);
        }

        $context = '';

        // Extract content from blocks
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $current_block) {
                // Skip the current block
                continue;
            }

            $block_content = self::getBlockContent($blocks[$i]);
            if (!empty($block_content)) {
                $context .= $block_content . "\n\n";
            }
        }

        return trim($context);
    }

    /**
     * Extract content from a block.
     *
     * @since    1.0.0
     * @param    array    $block    Block data.
     * @return   string             Block content.
     */
    public static function getBlockContent($block) {
        // Skip empty blocks
        if (empty($block)) {
            return '';
        }

        // Handle different block types using the BlockContextHandler
        switch ($block['blockName']) {
            case 'core/paragraph':
                return BlockContextHandler::processParagraph($block);

            case 'core/heading':
                return BlockContextHandler::processHeading($block);

            case 'core/list':
                return BlockContextHandler::processList($block);

            case 'core/quote':
                return BlockContextHandler::processQuote($block);

            case 'core/table':
                return BlockContextHandler::processTable($block);

            case 'core/image':
                return BlockContextHandler::processImage($block);

            case 'core/file':
                return BlockContextHandler::processFile($block);
                
            case 'core/code':
                return BlockContextHandler::processCode($block);
                
            case 'core/embed':
                return BlockContextHandler::processEmbed($block);
                
            case 'core/columns':
                return BlockContextHandler::processColumns($block);
                
            case 'neuron-ai/neuron-text':
                return BlockContextHandler::processNeuronText($block);
                
            case 'neuron-ai/neuron-search':
                return BlockContextHandler::processNeuronSearch($block);

            default:
                // For other blocks, check if they have inner blocks
                if (!empty($block['innerBlocks'])) {
                    $inner_content = '';
                    foreach ($block['innerBlocks'] as $inner_block) {
                        $inner_content .= self::getBlockContent($inner_block) . "\n";
                    }
                    return trim($inner_content);
                }

                // Default to extracted HTML content
                return wp_strip_all_tags($block['innerHTML']);
        }
    }

    /**
     * Extract context from a post.
     *
     * @since    1.0.0
     * @param    int      $post_id         Post ID.
     * @param    int      $current_block   Current block index (optional).
     * @param    int      $context_range   Number of blocks to include (optional).
     * @return   string                    Extracted context.
     */
    public static function extractFromPost($post_id, $current_block = null, $context_range = 3) {
        $post = get_post($post_id);
        
        if (!$post) {
            return '';
        }

        $context = '';

        // Add post title
        $context .= "Title: {$post->post_title}\n\n";

        // Add post excerpt if available
        if (!empty($post->post_excerpt)) {
            $context .= "Excerpt: {$post->post_excerpt}\n\n";
        }
        
        // Add post type
        $post_type_obj = get_post_type_object($post->post_type);
        if ($post_type_obj) {
            $context .= "Content Type: {$post_type_obj->labels->singular_name}\n\n";
        }

        // Get blocks from content
        $blocks = parse_blocks($post->post_content);
        
        // Add context from blocks
        $blocks_context = self::extractFromBlocks($blocks, $current_block, $context_range);
        if (!empty($blocks_context)) {
            $context .= "Content Context:\n{$blocks_context}\n\n";
        }

        // Add categories and tags
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $cat_names = array_map(function($cat) {
                return $cat->name;
            }, $categories);
            $context .= "Categories: " . implode(', ', $cat_names) . "\n\n";
        }

        $tags = get_the_tags($post_id);
        if (!empty($tags)) {
            $tag_names = array_map(function($tag) {
                return $tag->name;
            }, $tags);
            $context .= "Tags: " . implode(', ', $tag_names) . "\n\n";
        }
        
        // Add published date
        $context .= "Published: " . get_the_date('', $post_id) . "\n\n";
        
        // If it's a page, try to get the template
        if ($post->post_type === 'page') {
            $template = get_page_template_slug($post_id);
            if (!empty($template)) {
                $context .= "Page Template: {$template}\n\n";
            }
        }

        return trim($context);
    }

    /**
     * Extract context from blocks with tone analysis.
     *
     * @since    1.0.0
     * @param    array    $blocks          Array of blocks.
     * @param    int      $current_block   Current block index (optional).
     * @return   array                     Context with tone analysis.
     */
    public static function extractWithToneAnalysis($blocks, $current_block = null) {
        $context = self::extractFromBlocks($blocks, $current_block);
        
        // Perform tone analysis
        $tone = self::analyzeTone($context);
        
        return [
            'context' => $context,
            'tone' => $tone,
        ];
    }

    /**
     * Perform tone analysis on text.
     *
     * @since    1.0.0
     * @param    string    $text    Text to analyze.
     * @return   array              Tone analysis results.
     */
    private static function analyzeTone($text) {
        // Initialize tone analysis results with defaults
        $tone = [
            'formality' => 'neutral',      // formal, neutral, informal
            'sentiment' => 'neutral',      // positive, neutral, negative
            'reading_level' => 'medium',   // simple, medium, advanced
            'emotional_tone' => 'neutral', // professional, enthusiastic, serious, casual, neutral
            'voice' => 'active',           // active, passive
        ];
        
        // Skip if text is too short
        if (strlen($text) < 50) {
            return $tone;
        }
        
        // Clean text for analysis
        $clean_text = wp_strip_all_tags($text);
        $clean_text = strtolower($clean_text);
        
        // Word count for reading level analysis
        $word_count = str_word_count($clean_text);
        
        // Extract sentences
        $sentences = preg_split('/[.!?]+/', $clean_text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        if ($sentence_count > 0) {
            // Calculate average words per sentence
            $avg_words_per_sentence = $word_count / $sentence_count;
            
            // Calculate average sentence length
            $avg_sentence_length = strlen($clean_text) / $sentence_count;
            
            // Estimate reading level based on average words per sentence
            if ($avg_words_per_sentence > 25 || $avg_sentence_length > 130) {
                $tone['reading_level'] = 'advanced';
            } elseif ($avg_words_per_sentence > 15 || $avg_sentence_length > 90) {
                $tone['reading_level'] = 'medium';
            } else {
                $tone['reading_level'] = 'simple';
            }
        }
        
        // Analyze formality
        $formal_indicators = [
            'however', 'therefore', 'consequently', 'furthermore', 'nevertheless',
            'although', 'whereas', 'hence', 'thus', 'regarding', 'concerning',
            'additionally', 'moreover', 'subsequently', 'accordingly', 'hereby',
            'notwithstanding', 'pursuant', 'aforementioned', 'hitherto',
            'shall', 'must', 'ought', 'hereby', 'herein', 'therein',
        ];
        
        $informal_indicators = [
            'really', 'actually', 'basically', 'honestly', 'pretty',
            'just', 'so', 'totally', 'awesome', 'cool', 'amazing',
            'yeah', 'yep', 'nope', 'kinda', 'sorta', 'dunno', 'wanna',
            'gonna', 'gotta', 'ain\'t', 'stuff', 'things', 'like',
            'super', 'ok', 'okay', 'hey', 'wow', 'damn', 'shit',
        ];
        
        // Analyze sentiment
        $positive_indicators = [
            'good', 'great', 'excellent', 'wonderful', 'positive',
            'happy', 'best', 'love', 'enjoy', 'pleased', 'impressive',
            'success', 'beneficial', 'effective', 'efficient', 'useful',
            'advantage', 'innovative', 'improvement', 'enhance', 'optimize',
            'achievement', 'perfect', 'ideal', 'outstanding', 'superb',
        ];
        
        $negative_indicators = [
            'bad', 'poor', 'terrible', 'awful', 'negative',
            'sad', 'worst', 'hate', 'dislike', 'disappointed', 'unfortunately',
            'problem', 'issue', 'concern', 'difficulty', 'drawback',
            'failure', 'ineffective', 'inefficient', 'useless', 'disadvantage',
            'disappointing', 'frustrating', 'inadequate', 'insufficient',
        ];
        
        // Analyze emotional tone
        $professional_indicators = [
            'analyze', 'consider', 'determine', 'evaluate', 'examine',
            'recommend', 'suggest', 'conclude', 'implement', 'strategy',
            'objective', 'process', 'method', 'approach', 'assessment',
        ];
        
        $enthusiastic_indicators = [
            'exciting', 'thrilling', 'amazing', 'incredible', 'fantastic',
            'wonderful', 'awesome', 'extraordinary', 'excellent', 'brilliant',
            'exceptional', 'outstanding', 'remarkable', 'sensational', 'spectacular',
        ];
        
        $serious_indicators = [
            'critical', 'crucial', 'essential', 'imperative', 'fundamental',
            'significant', 'substantial', 'severe', 'serious', 'grave',
            'important', 'vital', 'necessary', 'required', 'mandatory',
        ];
        
        $passive_voice_indicators = [
            ' is ', ' are ', ' was ', ' were ', ' be ', ' been ', ' being ',
            ' has been ', ' have been ', ' had been ',
            ' will be ', ' will have been ',
            ' can be ', ' could be ', ' may be ', ' might be ', ' must be ', ' should be ',
        ];
        
        // Count occurrences of each indicator type
        $formal_count = 0;
        $informal_count = 0;
        $positive_count = 0;
        $negative_count = 0;
        $professional_count = 0;
        $enthusiastic_count = 0;
        $serious_count = 0;
        $passive_voice_count = 0;
        
        // Formality analysis
        foreach ($formal_indicators as $word) {
            $formal_count += self::countWordInText($word, $clean_text);
        }
        
        foreach ($informal_indicators as $word) {
            $informal_count += self::countWordInText($word, $clean_text);
        }
        
        // Sentiment analysis
        foreach ($positive_indicators as $word) {
            $positive_count += self::countWordInText($word, $clean_text);
        }
        
        foreach ($negative_indicators as $word) {
            $negative_count += self::countWordInText($word, $clean_text);
        }
        
        // Emotional tone analysis
        foreach ($professional_indicators as $word) {
            $professional_count += self::countWordInText($word, $clean_text);
        }
        
        foreach ($enthusiastic_indicators as $word) {
            $enthusiastic_count += self::countWordInText($word, $clean_text);
        }
        
        foreach ($serious_indicators as $word) {
            $serious_count += self::countWordInText($word, $clean_text);
        }
        
        // Passive voice analysis
        foreach ($passive_voice_indicators as $phrase) {
            $passive_voice_count += substr_count($clean_text, $phrase);
        }
        
        // Normalize counts by text length for more accurate comparison
        $text_length_factor = max(1, $word_count / 100); // Per 100 words
        
        $formal_score = $formal_count / $text_length_factor;
        $informal_score = $informal_count / $text_length_factor;
        $positive_score = $positive_count / $text_length_factor;
        $negative_score = $negative_count / $text_length_factor;
        $professional_score = $professional_count / $text_length_factor;
        $enthusiastic_score = $enthusiastic_count / $text_length_factor;
        $serious_score = $serious_count / $text_length_factor;
        $passive_score = $passive_voice_count / $text_length_factor;
        
        // Determine formality
        if ($formal_score > $informal_score * 1.5) {
            $tone['formality'] = 'formal';
        } elseif ($informal_score > $formal_score * 1.5) {
            $tone['formality'] = 'informal';
        } else {
            $tone['formality'] = 'neutral';
        }
        
        // Determine sentiment
        if ($positive_score > $negative_score * 1.5) {
            $tone['sentiment'] = 'positive';
        } elseif ($negative_score > $positive_score * 1.5) {
            $tone['sentiment'] = 'negative';
        } else {
            $tone['sentiment'] = 'neutral';
        }
        
        // Determine emotional tone
        $emotional_scores = [
            'professional' => $professional_score,
            'enthusiastic' => $enthusiastic_score,
            'serious' => $serious_score,
            'neutral' => 1, // Base score for neutral
        ];
        
        // If informal is high, add 'casual' as an option
        if ($informal_score > 2) {
            $emotional_scores['casual'] = $informal_score;
        }
        
        // Get the highest scoring emotional tone
        $tone['emotional_tone'] = array_search(max($emotional_scores), $emotional_scores);
        
        // Determine voice (active vs passive)
        // Average passive voice markers per 100 words
        if ($passive_score > 3) { // More than 3 passive constructions per 100 words
            $tone['voice'] = 'passive';
        } else {
            $tone['voice'] = 'active';
        }
        
        return $tone;
    }
    
    /**
     * Count occurrences of a word in text, ensuring word boundaries.
     *
     * @since    1.0.0
     * @param    string    $word    The word to count.
     * @param    string    $text    The text to search in.
     * @return   int                Number of occurrences.
     */
    private static function countWordInText($word, $text) {
        $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
        $matches = [];
        preg_match_all($pattern, $text, $matches);
        return count($matches[0]);
    }

    /**
     * Build options based on tone analysis.
     *
     * @since    1.0.0
     * @param    array    $tone_analysis    Tone analysis results.
     * @return   array                      AI enhancement options.
     */
    public static function buildOptionsFromToneAnalysis($tone_analysis) {
        $options = [];
        
        // Set tone based on formality and emotional tone
        if (isset($tone_analysis['formality']) && isset($tone_analysis['emotional_tone'])) {
            // Map formality and emotional tone to tone option
            if ($tone_analysis['formality'] === 'formal') {
                if ($tone_analysis['emotional_tone'] === 'professional') {
                    $options['tone'] = 'professional';
                } elseif ($tone_analysis['emotional_tone'] === 'serious') {
                    $options['tone'] = 'academic';
                } else {
                    $options['tone'] = 'professional';
                }
            } elseif ($tone_analysis['formality'] === 'informal') {
                if ($tone_analysis['emotional_tone'] === 'enthusiastic' || $tone_analysis['emotional_tone'] === 'casual') {
                    $options['tone'] = 'casual';
                } else {
                    $options['tone'] = 'casual';
                }
            } else {
                // Neutral formality
                if ($tone_analysis['emotional_tone'] === 'enthusiastic') {
                    $options['tone'] = 'creative';
                } elseif ($tone_analysis['emotional_tone'] === 'professional') {
                    $options['tone'] = 'professional';
                } elseif ($tone_analysis['emotional_tone'] === 'serious') {
                    $options['tone'] = 'academic';
                } else {
                    $options['tone'] = 'professional'; // Default
                }
            }
        }
        
        // Set reading level
        if (isset($tone_analysis['reading_level'])) {
            switch ($tone_analysis['reading_level']) {
                case 'advanced':
                    $options['reading_level'] = 'advanced';
                    break;
                case 'medium':
                    $options['reading_level'] = 'intermediate';
                    break;
                case 'simple':
                    $options['reading_level'] = 'simple';
                    break;
                default:
                    $options['reading_level'] = 'universal';
            }
        }
        
        // Add voice preference
        if (isset($tone_analysis['voice'])) {
            $options['voice'] = $tone_analysis['voice'];
        }
        
        // Set sentiment guidance
        if (isset($tone_analysis['sentiment']) && $tone_analysis['sentiment'] !== 'neutral') {
            $options['maintain_sentiment'] = $tone_analysis['sentiment'];
        }
        
        return $options;
    }

    /**
     * Extract context from current editor content.
     *
     * @since    1.0.0
     * @param    array    $blocks           Array of blocks from the editor.
     * @param    int      $current_block    Current block index.
     * @param    int      $post_id          Current post ID (optional).
     * @return   array                      Context and enhancement options.
     */
    public static function extractEditorContext($blocks, $current_block, $post_id = null) {
        // Extract context with tone analysis
        $context_data = self::extractWithToneAnalysis($blocks, $current_block);
        
        // Build enhancement options based on tone analysis
        $options = self::buildOptionsFromToneAnalysis($context_data['tone']);
        
        // If post ID is provided, add post metadata
        if ($post_id) {
            $post_context = self::extractFromPost($post_id, $current_block);
            $context_data['context'] = $post_context . "\n\n" . $context_data['context'];
        }
        
        return [
            'context' => $context_data['context'],
            'options' => $options,
            'tone' => $context_data['tone'],
        ];
    }

    /**
     * Clean and truncate context for API requests.
     *
     * @since    1.0.0
     * @param    string    $context    The context to clean.
     * @param    int       $max_len    Maximum allowed length (optional).
     * @return   string                Cleaned and truncated context.
     */
    public static function cleanContext($context, $max_len = 2000) {
        // Remove extra whitespace
        $cleaned_context = preg_replace('/\s+/', ' ', $context);
        
        // Remove any HTML tags
        $cleaned_context = wp_strip_all_tags($cleaned_context);
        
        // Truncate if too long
        if (strlen($cleaned_context) > $max_len) {
            $cleaned_context = substr($cleaned_context, 0, $max_len - 3) . '...';
        }
        
        return $cleaned_context;
    }
    
    /**
     * Extract a summary of the content.
     *
     * @since    1.0.0
     * @param    string    $content    The content to summarize.
     * @param    int       $max_len    Maximum summary length (optional).
     * @return   string                The content summary.
     */
    public static function extractSummary($content, $max_len = 200) {
        // Clean the content
        $content = wp_strip_all_tags($content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Trim the content
        $content = trim($content);
        
        // If content is already short enough, return as is
        if (strlen($content) <= $max_len) {
            return $content;
        }
        
        // Try to trim at sentence boundary
        $sentences = preg_split('/(?<=[.!?])\s+/', $content);
        $summary = '';
        
        foreach ($sentences as $sentence) {
            $new_summary = $summary . ($summary ? ' ' : '') . $sentence;
            if (strlen($new_summary) > $max_len) {
                break;
            }
            $summary = $new_summary;
        }
        
        // If we couldn't get a full sentence, just truncate
        if (empty($summary)) {
            $summary = substr($content, 0, $max_len - 3) . '...';
        }
        
        return $summary;
    }
    
    /**
     * Detect main language of content.
     *
     * @since    1.0.0
     * @param    string    $content    Content to analyze.
     * @return   string                Detected language code or 'en'.
     */
    public static function detectLanguage($content) {
        // Default to English
        $language = 'en';
        
        // Get WordPress site language
        $site_language = get_locale();
        if (!empty($site_language)) {
            // Extract main language code (e.g., 'en' from 'en_US')
            $lang_parts = explode('_', $site_language);
            $language = $lang_parts[0];
        }
        
        // TODO: Implement more sophisticated language detection if needed
        // For now, we'll just use the site language as a reasonable default
        
        return $language;
    }
}