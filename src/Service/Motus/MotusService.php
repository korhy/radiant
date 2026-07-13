<?php

namespace App\Service\Motus;

class MotusService
{
    /**
     * Mots de 5 à 8 lettres, sans accents, mélangés une fois pour l'ordre du jour.
     */
    private const WORDS = [
        'SIROP', 'JUSTICE', 'SPORTS', 'SCIENCE', 'VITRES', 'SCENES', 'DEFAITES', 'MIROIR',
        'GANTS', 'BALLET', 'LAMPE', 'GORGES', 'CITRON', 'CLIENTES', 'VOITURE', 'JUPES',
        'VITRINE', 'VIOLON', 'FRONTS', 'ENFANTS', 'FORCES', 'FRUIT', 'VESTE', 'VOISINS',
        'TONNERRE', 'DELAIS', 'FORCE', 'BAGUE', 'GRELES', 'PLAGES', 'ORANGE', 'EPICES',
        'COURAGE', 'LILAS', 'HABITS', 'TERRE', 'RAISIN', 'AMIGOS', 'LARMES', 'ROUGE',
        'STYLO', 'CHANTS', 'CUISINE', 'PIECE', 'HEROS', 'BOUSSOLE', 'BOTTES', 'DOIGTS',
        'FLEUR', 'ELEVES', 'MONTAGNE', 'POULE', 'PILULES', 'RIRES', 'CHEMIN', 'CANARD',
        'PHOTO', 'ROUES', 'ROUTES', 'ROBES', 'LUNETTE', 'ESPOIR', 'IMAGE', 'PLUIES',
        'VOYAGE', 'PORTE', 'BAGUES', 'POULET', 'BUREAU', 'CHOUX', 'TOUNDRA', 'LUTTES',
        'BEAUTE', 'REVES', 'VAGUE', 'LANGUES', 'ARGENT', 'ROCHERS', 'GRACES', 'PEINTRE',
        'BUFFET', 'CASCADES', 'JAUNE', 'VENTRE', 'KARATE', 'IMAGES', 'GLACES', 'VOLCANS',
        'CHIEN', 'ARMOIRE', 'JARDIN', 'GUITARE', 'PATES', 'MONDES', 'MINUTES', 'GENOU',
        'MELON', 'AIGLE', 'BATAILLE', 'LUNES', 'EPAULE', 'HISTOIRE', 'CADRE', 'PATONS',
        'MONNAIES', 'VICTOIRE', 'REMEDES', 'VACHE', 'COLERE', 'OPERAS', 'BISCUIT', 'TERRASSE',
        'PRAIRIES', 'PIERRE', 'SUCRE', 'PIANOS', 'LIONS', 'BRISE', 'RIVIERES', 'RADIS',
        'TEMPETES', 'RENARD', 'MAINS', 'CHENE', 'VILLES', 'SEMAINE', 'HORIZONS', 'PLUIE',
        'AVION', 'BEURRE', 'TRAIN', 'CAHIER', 'PLUME', 'LUMIERES', 'DANSES', 'VALEUR',
        'CHATEAU', 'TIROIR', 'VENTES', 'JOURNAUX', 'POMMES', 'OIGNON', 'ACTEUR', 'ELEPHANT',
        'FORETS', 'MERLE', 'TALENT', 'TEINTE', 'PAPILLON', 'LIVRES', 'FACTURES', 'CALME',
        'AILLES', 'TENNIS', 'GROTTES', 'FRAISE', 'TORRENTS', 'RIDEAU', 'LEVRE', 'VANILLE',
        'BILLETS', 'TROUPES', 'PLANETES', 'BRUNES', 'LANGUE', 'FARINE', 'COURSE', 'SUCCES',
        'BLANC', 'COMBAT', 'AMOURS', 'TALON', 'POINGS', 'CLASSES', 'MAISON', 'BRUME',
        'BONHEUR', 'SUCRES', 'RIVAGE', 'CARROT', 'AVIONS', 'BRUNE', 'JOURNEE', 'MANTEAU',
        'SANDALE', 'FALAISE', 'VILLE', 'PEINE', 'IMMEUBLE', 'FAMILLE', 'COCHON', 'TALONS',
        'GATEAU', 'LUMIERE', 'MARCHES', 'DESERTS', 'CHAMBRES', 'MARCHAND', 'TOMATE', 'CHARME',
        'BRACELET', 'SOURIRE', 'POING', 'COUCHERS', 'ECRAN', 'LAMPES', 'PAPIER', 'NATURE',
        'ORAGEUX', 'DANSE', 'NEIGE', 'VENDEURS', 'ROUTE', 'OURAGANS', 'PAYSAGES', 'MAITRE',
        'CHEMINEE', 'BALEINES', 'SAPIN', 'BATEAUX', 'CRAYONS', 'SAUCE', 'BOUCHE', 'TABLE',
        'GUERRES', 'COLLIER', 'CASTOR', 'FABLE', 'ESCARGOT', 'TABLES', 'POIRES', 'FENETRES',
        'EFFORT', 'ROSES', 'CHANT', 'HERBES', 'PHOTOS', 'CONTE', 'JOIES', 'TORNADES',
        'GRAIN', 'MUSIQUE', 'SAUCES', 'ARBRES', 'TUNNELS', 'CALMES', 'STEPPES', 'BATIMENT',
        'MURES', 'DROITS', 'ESCALIER', 'MACHINE', 'LECONS', 'VERTE', 'CHEVAL', 'NATIONS',
        'CINEMA', 'CULTURE', 'PLAGE', 'HORLOGE', 'RAPPEL', 'FENETRE', 'OBSCURES', 'JARDINS',
        'DIPLOME', 'STYLES', 'CHATS', 'PERLE', 'DEVOIRS', 'PLANTE', 'CAMPAGNE', 'FRUITS',
        'SCENE', 'ARAIGNEE', 'LIVRE', 'PIECES', 'NOIRE', 'LIBERTE', 'TEMPETE', 'VENTS',
        'SOLEIL', 'BANANE', 'LIEVRE', 'GALAXIES', 'CEINTURE', 'JAMBE', 'CHATON', 'NUAGE',
        'CAPITALE', 'DAUPHINS', 'PROJETS', 'LEGUME', 'TIGRES', 'EXAMENS', 'CHAUVE', 'CARTES',
        'FONTAINE', 'BOTTE', 'LARME', 'BOUGIES', 'AMOUR', 'PUBLIC', 'ORAGE', 'ETOILE',
        'MAREES', 'PIEDS', 'TRAINS', 'JOUES', 'ROMAN', 'CADRES', 'TAMBOUR', 'NUQUES',
        'VAGUES', 'BOUTIQUE', 'SENTIERS', 'MANGUE', 'SAVEURS', 'GENOUX', 'NUAGES', 'FLEURS',
        'AGENDA', 'SABLES', 'VACANCES', 'FLUTES', 'TIGRE', 'DESSINS', 'COUDES', 'COUDE',
        'MYTHE', 'MONTRES', 'CHEMISE', 'VESTES', 'SAISON', 'MOUTON', 'STYLOS', 'SABLE',
        'LEVRES', 'GIVRE',
    ];

    public function getWordOfTheDay(): string
    {
        $day = (int) (new \DateTimeImmutable())->format('z');

        return self::WORDS[$day % count(self::WORDS)];
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
