<?php

return [
    // Largest roster a team can build. A team that has entered a tournament is further limited
    // by that tournament's team_size (see RosterService::limits()).
    'max_starters' => 5,
    'max_substitutes' => 2,
];
