<?php
declare(strict_types=1);

namespace Celestra;

class VectorSearch
{
    private array $dataset;
    private array $vocabulary = [];
    private array $documentVectors = [];
    private array $stopwords;

    public function __construct()
    {
        $this->dataset = [
            'Деньги валяются повсюду, нужно уметь их подбирать',
            'Сейчас нас будут бить. А нас то за что?',
            'Что разум человека может постигнуть и во что он может поверить, того он способен достичь',
            'Сложнее всего начать действовать, все остальное зависит только от упорства',
            'Надо любить жизнь больше, чем смысл жизни',
            'Логика может привести Вас от пункта А к пункту Б, а воображение — куда угодно',
            'Ваше время ограничено, не тратьте его, живя чужой жизнью',
            'Свобода ничего не стоит, если она не включает в себя свободу ошибаться',
            'Если вы думаете, что на что-то способны, вы правы; если думаете, что у вас ничего не получится - вы тоже правы',
            'Два самых важных дня в твоей жизни: день, когда ты появился на свет, и день, когда понял, зачем'
        ];

        $defaultStopwords = [
            'и','в','во','не','что','он','на','я','с','со','как','а','то','все','она','так','его','но','да',
            'ты','к','у','же','вы','за','бы','по','только','ее','мне','было','вот','от','меня','еще','нет','о','из','ему',
            'теперь','когда','даже','ну','вдруг','ли','если','уже','или','ни','быть','был','него','до','вас','нибудь','опять',
            'уж','вам','ведь','там','потом','себя','ничего','ей','может','они','тут','где','есть','надо','ней','для','мы','тебя',
            'их','чем','была','сам','чтоб','без','будто','чего','раз','тоже','себе','под','будет','ж','тогда','кто','этот','того',
            'потому','этого','какой','совсем','ним','здесь','этом','один','почти','мой','тем','чтобы','нее','кажется','сейчас','были',
            'куда','зачем','всех', 'можно','при','наконец','два','об','другой','хоть','после','над','больше','тот','через',
            'эти','нас','про','всего','них','какая','много','разве','три','эту','моя','впрочем','хорошо','свою','этой','перед',
            'иногда','лучше','чуть','том','нельзя','такой','им','более','всегда','конечно','всю','между',
            'and','or','the','a','an','is','are','to','of','for','on','in','with','by','as','at','be','this','that','from','it',
            'was','were','will','can','not'
        ];

        $stopwords = [];

        foreach ($defaultStopwords as $sw) {
            $stopwords[$sw] = true;
        }

        $this->stopwords = $stopwords;

        $this->buildVocabulary();
        $this->buildDocumentVectors();
    }

    private function buildVocabulary(): void
    {
        $vocabulary = [];

        foreach ($this->dataset as $text) {
            foreach ($this->tokenize($text) as $token) {
                $vocabulary[$token] = true;
            }
        }

        $this->vocabulary = $vocabulary;
    }

    private function tokenize(string $text): array
    {
        $tokens = [];
        $lower = mb_strtolower($text, 'UTF-8');
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $lower) ?: [];

        foreach ($parts as $part) {
            if ($part === '' || isset($this->stopwords[$part])) {
                continue;
            }
            $tokens[] = $part;
        }

        return $tokens;
    }

    private function buildDocumentVectors(): void
    {
        $vectors = [];

        foreach ($this->dataset as $index => $text) {
            $vectors[$index] = $this->vectorizeText($text);
        }

        $this->documentVectors = $vectors;
    }

    private function findClosestToken(string $token): ?string
    {
        $minDistance = 3;
        $closest = null;

        foreach ($this->vocabulary as $vocabToken => $_) {
            $distance = levenshtein($token, $vocabToken);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closest = $vocabToken;
            }
        }

        return $closest;
    }

    private function vectorizeText(string $text): array
    {
        $vector = [];

        foreach ($this->tokenize($text) as $token) {
            $stem = $this->simpleStem(mb_strtolower($token));

            if (!isset($this->vocabulary[$stem])) {
                $stem = $this->findClosestToken($stem);
                
                if ($stem === null) {
                    continue;
                }
            }

            if (!isset($vector[$stem])) {
                $vector[$stem] = 0.0;
            }

            $vector[$stem] += 1.0;
        }

        return $vector;
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $queryVector = $this->vectorizeText($query);

        $results = [];

        foreach ($this->documentVectors as $index => $docVector) {
            $coincidence = $this->cosineCoincidence($queryVector, $docVector);
            $results[] = [
                'text' => $this->dataset[$index],
                'coincidence' => $coincidence,
            ];
        }

        usort($results, [$this, 'sortRules']);

        return $results;
    }

    private function sortRules(array $a, array $b): int {
        if ($a['coincidence'] === $b['coincidence']) {
            return 0;
        }

        return ($a['coincidence'] > $b['coincidence']) ? -1 : 1;
    }

    private function cosineCoincidence(array $v1, array $v2): float
    {
        if (empty($v1) || empty($v2)) {
            return 0.0;
        }

        $stemmedV1 = $this->stemArrayKeys($v1);
        $stemmedV2 = $this->stemArrayKeys($v2);

        $dot = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        foreach ($stemmedV1 as $token1 => $value1) {
            $norm1 += $value1 * $value1;
            $bestMatch = null;
            $minDistance = 3; 

            foreach ($stemmedV2 as $token2 => $value2) {
                $distance = levenshtein($token1, $token2);

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $token2;
                }
            }

            if ($bestMatch !== null) {
                $dot += $value1 * $stemmedV2[$bestMatch];
            }
        }

        foreach ($stemmedV2 as $value2) {
            $norm2 += $value2 * $value2;
        }

        if ($norm1 <= 0.0 || $norm2 <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($norm1) * sqrt($norm2));
    }

    private function stemArrayKeys(array $arr): array
    {
        $stemmed = [];

        foreach ($arr as $key => $value) {
            $stem = $this->simpleStem($key);
            $stemmed[$stem] = $value;
        }

        return $stemmed;
    }

    private function simpleStem($word)
    {
        return preg_replace(
            '/(а|ы|е|и|о|у|ю|ой|ей|ом|ем|ём|ый|ий|ая|яя|ое|ее|ие|ые|ешь|ишь|ет|ит|ем|им|ете|ите|ёте|ут|ют|ат|ят|ами|ями)$/u',
            '',
            $word
        );
    }
}


