<?php
/**
 * Validation Helper
 * Centralizes all input validation and whitelists.
 */

class ValidationHelper
{
    private static $allowed_models = ['dall-e-3', 'gpt-image-1.5', 'gpt-image-1-mini'];
    private static $allowed_resolutions = ['1:1', '16:9', '9:16'];
    private static $allowed_formats = ['png', 'jpg', 'jpeg'];

    /**
     * Clean and validate model identifier
     */
    public static function validateModel($rawModel)
    {
        $model = preg_replace('/[^a-z0-9\-\.]/', '', $rawModel);
        if (!in_array($model, self::$allowed_models)) {
            return ['success' => false, 'error' => 'Invalid model.'];
        }
        return ['success' => true, 'data' => $model];
    }

    /**
     * Clean and validate resolution
     */
    public static function validateResolution($res)
    {
        if (!in_array($res, self::$allowed_resolutions)) {
            return ['success' => false, 'error' => 'Invalid resolution.'];
        }
        return ['success' => true, 'data' => $res];
    }

    /**
     * Clean and validate format
     */
    public static function validateFormat($format)
    {
        if (!in_array($format, self::$allowed_formats)) {
            return ['success' => false, 'error' => 'Invalid format.'];
        }
        return ['success' => true, 'data' => $format];
    }
}
