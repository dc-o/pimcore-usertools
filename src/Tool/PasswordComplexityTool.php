<?php
namespace DCO\UserTools\Tool;

class PasswordComplexityTool {
    public int $Length = 0;
    public int $LowercaseCharacters = 0;
    public int $UppercaseCharacters = 0;
    public int $Numbers = 0;
    public int $Symbols = 0;
    public static function rankPassword($passwd) : PasswordComplexityTool {
        $ranking = new PasswordComplexityTool();
        $ranking->Length = strlen($passwd);

        $matches = [];
        preg_match_all("/([a-z]+)/", $passwd, $matches);
        $ranking->LowercaseCharacters = strlen(implode('', $matches[0]));

        $matches = [];
        preg_match_all("/([A-Z]+)/", $passwd, $matches);
        $ranking->UppercaseCharacters = strlen(implode('', $matches[0]));

        $matches = [];
        preg_match_all("/([0-9]+)/", $passwd, $matches);
        $ranking->Numbers = strlen(implode('', $matches[0]));

        $matches = [];
        preg_match_all("/([^a-zA-Z0-9]+)/", $passwd, $matches);
        $ranking->Symbols = strlen(implode('', $matches[0]));

        return $ranking;
    }
    public function getRanking() {
        return (
        ($this->LowercaseCharacters > 0 ? 1 : 0)
        + ($this->UppercaseCharacters > 0 ? 1 : 0)
        + ($this->Numbers > 0 ? 1 : 0)
        + ($this->Symbols > 0 ? 1 : 0)
        ) / 4
            ;
    }
    
}
