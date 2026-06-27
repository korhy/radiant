<?php

namespace App\Service\Motus;

class MotusService
{
    private const WORDS = [
        'MAISON', 'JARDIN', 'SOLEIL', 'FLEURS', 'CHEMIN', 'PLANTE', 'FORÊTS', 'PIERRE',
        'RIVAGE', 'NUAGES', 'ÉTOILE', 'LUMIÈRE', 'SAISON', 'CHEVAL', 'MOUTON', 'LAPIN',
        'POULET', 'CANARD', 'TIGRES', 'LIÈVRE', 'RENARD', 'CASTOR', 'HERBES', 'ARBRES',
        'FRUITS', 'LÉGUME', 'POMMES', 'CERISES', 'CITRON', 'ORANGE', 'BANANE', 'RAISIN',
        'FRAISE', 'MANGUE', 'POIRES', 'TOMATE', 'CARROT', 'RADIS',  'NAVET',  'OIGNON',
        'AILLES', 'MOUTON', 'COCHON', 'CHATON', 'CHAUVE', 'BLONDES', 'BRUNES', 'TEINTE',
        'VENTRE', 'ÉPAULE', 'GENOUX', 'COUDES', 'DOIGTS', 'POINGS', 'TALONS', 'NUQUES',
        'BOUCHE', 'GORGES', 'LANGUE', 'DENTS',  'LÈVRES', 'JOUES',  'FRONTS', 'REGARDS',
        'SOURIRE', 'LARMES', 'COLÈRE', 'TRISTESSE', 'BONHEUR', 'AMOURS', 'PASSIONS',
        'RÊVES',  'ESPOIR', 'CONFIANCE', 'COURAGE', 'FORCES', 'ÉNERGIE', 'VOLONTÉ',
        'TALENT', 'SUCCÈS', 'DÉFAITE', 'COMBAT', 'LUTTES', 'EFFORT', 'MÉRITES',
        'VALEUR', 'BEAUTÉ', 'CHARME', 'GRÂCES', 'ÉLÉGANCE', 'STYLES', 'MODES',
        'TABLES', 'CHAISES', 'ARMOIRE', 'BUFFET', 'TIROIR', 'VITRES', 'RIDEAUX',
        'LAMPES', 'BOUGIES', 'MIROIR', 'CADRES', 'LIVRES', 'PAPIER', 'STYLOS',
        'CRAYONS', 'CAHIER', 'AGENDA', 'AGENDA', 'PHOTOS', 'IMAGES', 'DESSINS',
        'PEINTRE', 'MUSIQUE', 'GUITARE', 'VIOLON', 'PIANOS', 'FLÛTES', 'TAMBOUR',
        'CHANTS', 'DANSES', 'BALLET', 'OPÉRAS', 'FILMS',  'CINÉMA', 'SCÈNES',
        'ACTEUR', 'TROUPES', 'PIÈCES', 'RIDEAU', 'PUBLIC', 'BRAVO',  'RAPPEL',
        'VOYAGE', 'TRAINS', 'AVIONS', 'BATEAUX', 'ROUTES', 'PONTS',  'TUNNELS',
        'VILLES', 'CAPITALES', 'PAYS',   'MONDES', 'CARTES', 'BOUSSOLE', 'ÉTOILES',
        'MARÉES', 'VAGUES', 'PLAGES', 'SABLES', 'ROCHERS', 'GROTTES', 'FALAISE',
        'VOLCANS', 'DÉSERTS', 'STEPPES', 'TOUNDRA', 'GLACES',  'NEIGE',  'PLUIES',
        'TEMPÊTE', 'ORAGEUX', 'GRÊLES', 'VENTES', 'BRISE',  'CALMES', 'SEREINS',
        'REPAS',  'CUISINES', 'SAVEURS', 'ÉPICES', 'SAUCES', 'PLATS',  'DESSERTS',
        'GÂTEAU', 'BISCUITS', 'CROÛTES', 'PÂTONS', 'FARINE', 'BEURRE', 'SUCRES',
        'MIEL',   'SIROP',  'CARAMEL', 'CHOCOLAT', 'VANILLE', 'CANNELLE', 'ANIS',
        'SPORTS', 'FOOTBALL', 'TENNIS', 'BOXE',   'JUDO',   'KARATÉ', 'NATATION',
        'COURSE', 'CYCLISME', 'ESCALADE', 'PLONGEON', 'SAUT',  'LANCERS', 'DISQUES',
        'ÉCOLE',  'CLASSES', 'ÉLÈVES', 'MAÎTRE', 'LEÇONS', 'DEVOIRS', 'EXAMENS',
        'DIPLÔMES', 'MÉDECIN', 'HÔPITAL', 'SOINS',  'REMÈDES', 'PILULES', 'INJECTIONS',
        'TRAVAIL', 'BUREAU', 'PROJETS', 'ÉQUIPES', 'RÉUNIONS', 'DÉLAIS', 'BUDGETS',
        'ARGENT', 'MONNAIE', 'BILLETS', 'PIÈCES',  'MARCHÉS', 'BOUTIQUES', 'VITRINES',
        'HABITS', 'MANTEAU', 'VESTES',  'CHEMISE', 'PANTALONS', 'ROBES',  'JUPES',
        'SOULIERS', 'BOTTES', 'SANDALES', 'CEINTURES', 'SACS',   'PORTEFEUILLES',
        'MONTRES', 'BAGUES',  'COLLIER', 'BRACELETS', 'BOUCLES', 'LUNETTES',
        'ANIMAUX', 'NATURE',  'SCIENCES', 'HISTOIRE', 'CULTURES', 'LANGUES', 'PEUPLES',
        'FAMILLES', 'PARENTS', 'ENFANTS', 'AMIGOS', 'VOISINS', 'SOCIÉTÉ', 'NATIONS',
        'PAIX',   'GUERRES', 'DROITS',  'LIBERTÉ', 'JUSTICE', 'ÉGALITÉ', 'FRATERNITÉ',
    ];

    /** @return string[] */
    private function getValidWords(): array
    {
        return array_values(array_filter(self::WORDS, fn (string $w) => 6 === mb_strlen($w)));
    }

    public function getWordOfTheDay(): string
    {
        $words = $this->getValidWords();
        $day = (int) (new \DateTimeImmutable())->format('z');

        return $words[$day % count($words)];
    }

    /**
     * @return array<int, array{letter: string, state: string}>
     */
    public function checkGuess(string $guess, string $word): array
    {
        $guess = mb_strtoupper($guess);
        $word = mb_strtoupper($word);
        $len = mb_strlen($word);
        $result = array_fill(0, $len, null);

        $wordLetters = [];
        for ($i = 0; $i < $len; ++$i) {
            $wordLetters[] = mb_substr($word, $i, 1);
        }

        $remaining = $wordLetters;

        // First pass: correct positions
        for ($i = 0; $i < $len; ++$i) {
            $letter = mb_substr($guess, $i, 1);
            if ($letter === $wordLetters[$i]) {
                $result[$i] = ['letter' => $letter, 'state' => 'correct'];
                $remaining[$i] = null;
            }
        }

        // Second pass: present but wrong position
        for ($i = 0; $i < $len; ++$i) {
            if (null !== $result[$i]) {
                continue;
            }
            $letter = mb_substr($guess, $i, 1);
            $foundAt = array_search($letter, $remaining, true);
            if (false !== $foundAt) {
                $result[$i] = ['letter' => $letter, 'state' => 'present'];
                $remaining[$foundAt] = null;
            } else {
                $result[$i] = ['letter' => $letter, 'state' => 'absent'];
            }
        }

        return $result;
    }
}
