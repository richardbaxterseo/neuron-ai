<?php
/**
 * Block Context Handler utility class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Utils
 */

namespace NeuronAI\Utils;

/**
 * Block Context Handler class.
 *
 * Specialized utility for extracting context from different Gutenberg block types.
 */
class BlockContextHandler {

    /**
     * Process a core/paragraph block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processParagraph($block) {
        return wp_strip_all_tags($block['innerHTML']);
    }

    /**
     * Process a core/heading block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processHeading($block) {
        $content = wp_strip_all_tags($block['innerHTML']);
        $level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
        
        // Return heading with semantic importance marker
        return "[Heading Level {$level}] {$content}";
    }

    /**
     * Process a core/list block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processList($block) {
        $content = wp_strip_all_tags($block['innerHTML']);
        $list_type = isset($block['attrs']['ordered']) && $block['attrs']['ordered'] ? 'ordered' : 'unordered';
        
        // Extract list items, maintaining the list structure
        $items = preg_split('/\r\n|\r|\n/', $content);
        $cleaned_items = array_map('trim', array_filter($items));
        
        $formatted_list = "[{$list_type} list]\n";
        foreach ($cleaned_items as $index => $item) {
            if ($list_type === 'ordered') {
                $formatted_list .= ($index + 1) . ". {$item}\n";
            } else {
                $formatted_list .= "- {$item}\n";
            }
        }
        
        return $formatted_list;
    }

    /**
     * Process a core/quote block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processQuote($block) {
        $content = wp_strip_all_tags($block['innerHTML']);
        $citation = isset($block['attrs']['citation']) ? $block['attrs']['citation'] : '';
        
        $quote = "> {$content}";
        if (!empty($citation)) {
            $quote .= "\n> — {$citation}";
        }
        
        return $quote;
    }

    /**
     * Process a core/table block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processTable($block) {
        // For tables, we'll extract a simplified representation
        $content = wp_strip_all_tags($block['innerHTML']);
        
        // Try to extract header information if available
        $has_header = isset($block['attrs']['hasFixedLayout']) ? $block['attrs']['hasFixedLayout'] : false;
        
        $intro = "[Table" . ($has_header ? " with header" : "") . "]\n";
        return $intro . $content;
    }

    /**
     * Process a core/image block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processImage($block) {
        $alt_text = isset($block['attrs']['alt']) ? $block['attrs']['alt'] : '';
        $caption = isset($block['attrs']['caption']) ? $block['attrs']['caption'] : '';
        $url = isset($block['attrs']['url']) ? $block['attrs']['url'] : '';
        
        $img_info = [];
        
        if (!empty($alt_text)) {
            $img_info[] = "Alt text: {$alt_text}";
        }
        
        if (!empty($caption)) {
            $img_info[] = "Caption: {$caption}";
        }
        
        if (!empty($url)) {
            // Extract filename from URL for additional context
            $filename = basename(parse_url($url, PHP_URL_PATH));
            if (!empty($filename)) {
                $img_info[] = "Filename: {$filename}";
            }
        }
        
        if (!empty($img_info)) {
            return "[Image] " . implode(', ', $img_info);
        }
        
        return "[Image without description]";
    }

    /**
     * Process a core/file block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processFile($block) {
        $file_name = isset($block['attrs']['fileName']) ? $block['attrs']['fileName'] : '';
        $description = isset($block['attrs']['description']) ? $block['attrs']['description'] : '';
        $url = isset($block['attrs']['href']) ? $block['attrs']['href'] : '';
        
        if (!empty($file_name)) {
            return "[File] {$file_name}" . (!empty($description) ? ": {$description}" : '');
        } elseif (!empty($url)) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            return "[File] {$filename}" . (!empty($description) ? ": {$description}" : '');
        }
        
        return "[File attachment]";
    }

    /**
     * Process a core/code block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processCode($block) {
        $content = isset($block['attrs']['content']) ? $block['attrs']['content'] : wp_strip_all_tags($block['innerHTML']);
        $language = isset($block['attrs']['language']) ? $block['attrs']['language'] : '';
        
        // Include code block with language information but limit length
        $content = (strlen($content) > 100) ? substr($content, 0, 100) . '...' : $content;
        return "[Code" . (!empty($language) ? " ({$language})" : "") . "]\n{$content}";
    }

    /**
     * Process a core/embed block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processEmbed($block) {
        $provider = isset($block['attrs']['providerNameSlug']) ? $block['attrs']['providerNameSlug'] : '';
        $url = isset($block['attrs']['url']) ? $block['attrs']['url'] : '';
        $caption = isset($block['attrs']['caption']) ? $block['attrs']['caption'] : '';
        
        $embed_info = "[Embedded content";
        
        if (!empty($provider)) {
            $embed_info .= " from {$provider}";
        }
        
        $embed_info .= "]";
        
        if (!empty($caption)) {
            $embed_info .= "\nCaption: {$caption}";
        }
        
        if (!empty($url)) {
            $embed_info .= "\nURL: {$url}";
        }
        
        return $embed_info;
    }

    /**
     * Process a core/columns block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processColumns($block) {
        if (empty($block['innerBlocks'])) {
            return "[Columns layout with no content]";
        }
        
        $column_count = count($block['innerBlocks']);
        $intro = "[Columns layout with {$column_count} columns]\n";
        
        $content = '';
        foreach ($block['innerBlocks'] as $index => $column) {
            $content .= "[Column " . ($index + 1) . "]\n";
            if (!empty($column['innerBlocks'])) {
                foreach ($column['innerBlocks'] as $inner_block) {
                    $inner_content = ContextExtractor::getBlockContent($inner_block);
                    if (!empty($inner_content)) {
                        $content .= $inner_content . "\n";
                    }
                }
            }
        }
        
        return $intro . $content;
    }

    /**
     * Process a neuron-ai/neuron-text block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processNeuronText($block) {
        $content = isset($block['attrs']['content']) ? $block['attrs']['content'] : '';
        $conversations = isset($block['attrs']['conversations']) ? $block['attrs']['conversations'] : [];
        
        if (!empty($content)) {
            // For basic content, format as AI-enhanced
            return "[AI-Enhanced Content] " . wp_strip_all_tags($content);
        } elseif (!empty($conversations)) {
            // For conversation mode, extract last assistant response
            $last_response = '';
            foreach (array_reverse($conversations) as $message) {
                if (isset($message['role']) && $message['role'] === 'assistant' && isset($message['content'])) {
                    $last_response = $message['content'];
                    break;
                }
            }
            
            if (!empty($last_response)) {
                return "[AI Conversation] " . wp_strip_all_tags($last_response);
            }
        }
        
        return "[AI-Enhanced Block]";
    }

    /**
     * Process a neuron-ai/neuron-search block.
     *
     * @since    1.0.0
     * @param    array    $block    The block data.
     * @return   string             Extracted content.
     */
    public static function processNeuronSearch($block) {
        $query = isset($block['attrs']['query']) ? $block['attrs']['query'] : '';
        $results = isset($block['attrs']['results']) ? $block['attrs']['results'] : '';
        
        $output = '';
        if (!empty($query)) {
            $output .= "[AI Search Query] {$query}\n";
        }
        
        if (!empty($results)) {
            // Limit the search results summary to a manageable size
            $output .= "[AI Search Results Summary] " . wp_trim_words(wp_strip_all_tags($results), 30);
        }
        
        return !empty($output) ? $output : "[AI Search Block]";
    }
}