<?php
/**
 * Created by PhpStorm.
 * User: Dionisio Gabriel Rigo de Souza Machado (https://github.com/diomac)
 * Date: 19/10/2018
 * Time: 10:37
 */

namespace Diomac\API;

use ReflectionException;
use ReflectionClass;
use stdClass;
use Exception;

/**
 * Class Annotation
 * @package Diomac\API
 */
class Annotation extends ReflectionClass
{
    /**
     * Annotation constructor.
     * @param null $class
     * @throws ReflectionException
     */
    public function __construct($class = null)
    {
        if ($class) {
            parent::__construct($class);
        } else {
            parent::__construct(self::class);
        }
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @return string|null
     */
    public function simpleAnnotationToString(string $annotation, string $tag): ?string
    {
        $pregResult = null;

        preg_match('/@' . $tag . ' (.*)/', $annotation, $pregResult);

        if (!$pregResult) {
            return null;
        }

        return trim($pregResult[1]);
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @param callable $func
     * @return string[]|null
     */
    public function simpleAnnotationToArray(string $annotation, string $tag, callable $func = null): ?array
    {
        $pregResult = null;

        preg_match_all('/@' . $tag . ' (.*)/', $annotation, $pregResult);

        if (!$pregResult) {
            return null;
        }

        if ($func) {
            return array_map($func, $pregResult[1]);
        }

        return $pregResult[1];
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @param callable $func
     * @return stdClass[]
     * @throws Exception
     */
    public function complexAnnotationToArrayJSON(string $annotation, string $tag, callable $func = null): array
    {
        $array = [];
        $loop = $this->enumeratePregResult($annotation, $tag);

        for ($i = 0; $i < count($loop); $i++) {
            $array[] = $this->complexAnnotationToJSON($annotation, $tag);
            $annotation = preg_replace('/@' . $tag . '/', '', $annotation, 1);
        }

        if ($func) {
            return array_map($func, $array);
        }

        return $array;
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @return mixed|null|stdClass
     * @throws Exception
     */
    public function complexAnnotationToJSON(string $annotation, string $tag): ?stdClass
    {

        $pregResult = null;
        $strSearch = ['(', ')', '=', ',', '*', '\\'];
        $strReplace = ['":{"', '}', '":', ',"', '', '\\\\'];

        $pregResult = $this->pregMatchComplexAnnotation($annotation, $tag);

        if (!$pregResult) {
            return null;
        }

        $json = preg_replace('/":/', '', str_replace($strSearch, $strReplace, $pregResult[1]), 1);

        $std = json_decode(preg_replace('/@|\s/', '', $json));

        if (!$std) {
            throw new Exception(
                'Bad documentation. Check PHPDoc @' . $tag,
                Response::INTERNAL_SERVER_ERROR
            );
        }

        return $std;
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @param string|null $closePar
     * @return array|null
     */
    private function pregMatchComplexAnnotation(string $annotation, string $tag, string $closePar = null): ?array
    {
        $pattern = '/@' . $tag . '(\([^)]*\)' . $closePar . ')/';

        preg_match($pattern, $annotation, $pregResult);

        if (!$pregResult) {
            return null;
        }

        $open = substr_count($pregResult[0], '(');
        $close = substr_count($pregResult[0], ')');

        if ($open > $close && !$closePar) {
            $closePar = str_repeat('[^)]*\)', $open - $close);
            $pregResult = $this->pregMatchComplexAnnotation($annotation, $tag, $closePar);
        }

        return $pregResult;
    }

    /**
     * @param string $annotation
     * @param string $tag
     * @return array
     */
    private function enumeratePregResult(string $annotation, string $tag): array
    {
        $pregResult = null;
        preg_match_all('/@' . $tag . '(\([^)]*\))/', $annotation, $pregResult);

        if (!$pregResult) {
            return [];
        }
        return $pregResult[0];
    }
}
