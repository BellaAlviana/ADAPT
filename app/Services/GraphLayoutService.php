<?php

namespace App\Services;

class GraphLayoutService
{
    protected int $laneWidth = 260;
    protected int $headerHeight = 34;
    protected int $nodeHeight = 48;
    protected int $nodeWidth = 160;
    protected int $rankSep = 86;

    /**
     * Compute dynamic dimensions for nodes based on type, label length, and line count
     * Action nodes: width 160-226px, height 46-76px
     */
    public function calculateNodeDimensions(array $node): array
    {
        $type = $node['type'] ?? 'activity';
        $label = trim($node['label'] ?? '');
        $len = mb_strlen($label);

        switch ($type) {
            case 'initial':
                return ['width' => 26, 'height' => 26];
            case 'final':
            case 'flow_final':
                return ['width' => 28, 'height' => 28];
            case 'decision':
            case 'merge':
                return ['width' => 44, 'height' => 44];
            case 'fork':
            case 'join':
                return ['width' => 160, 'height' => 8];
            case 'time_event':
                return ['width' => 38, 'height' => 44];
            case 'send_signal':
            case 'receive_signal':
                $w = min(220, max(150, (int)($len * 6.5) + 36));
                return ['width' => $w, 'height' => 48];
            case 'object':
                $w = min(200, max(130, (int)($len * 6.5) + 24));
                return ['width' => $w, 'height' => 38];
            default: // 'activity' / Action Node
                // Dynamic sizing: width 160px - 226px, height 46px - 76px based on label character length
                if ($len <= 20) {
                    $w = 160;
                    $h = 46;
                } elseif ($len <= 32) {
                    $w = 180;
                    $h = 50;
                } elseif ($len <= 44) {
                    $w = 200;
                    $h = 56;
                } elseif ($len <= 60) {
                    $w = 220;
                    $h = 66;
                } else {
                    $w = 226;
                    $h = 76;
                }
                return ['width' => $w, 'height' => $h];
        }
    }

    /**
     * Compute positions for partitions, nodes, and edges with clean Draw.io layout
     */
    public function layout(array $diagramData): array
    {
        $rawNodes = $diagramData['nodes'] ?? [];
        $rawEdges = $diagramData['edges'] ?? [];
        $rawPartitions = $diagramData['partitions'] ?? [];

        // 1. Resolve dynamic lane width and partitions (Swimlanes)
        $maxNodeWidth = 160;
        foreach ($rawNodes as $node) {
            $dim = $this->calculateNodeDimensions($node);
            $ntype = $node['type'] ?? 'activity';
            if ($ntype !== 'fork' && $ntype !== 'join') {
                $maxNodeWidth = max($maxNodeWidth, $dim['width']);
            }
        }
        $this->laneWidth = max(260, $maxNodeWidth + 80);

        $partitions = [];
        if (!empty($rawPartitions)) {
            foreach ($rawPartitions as $idx => $p) {
                $pName = is_array($p) ? ($p['name'] ?? "Lane {$idx}") : $p;
                $partitions[$pName] = [
                    'name' => $pName,
                    'index' => $idx,
                    'x' => 30 + ($idx * $this->laneWidth),
                    'width' => $this->laneWidth,
                ];
            }
        } else {
            $seenPartitions = [];
            foreach ($rawNodes as $n) {
                $p = $n['partition'] ?? 'User';
                if (!in_array($p, $seenPartitions)) {
                    $seenPartitions[] = $p;
                }
            }
            if (empty($seenPartitions)) {
                $seenPartitions[] = 'User';
            }
            foreach ($seenPartitions as $idx => $pName) {
                $partitions[$pName] = [
                    'name' => $pName,
                    'index' => $idx,
                    'x' => 30 + ($idx * $this->laneWidth),
                    'width' => $this->laneWidth,
                ];
            }
        }

        // 2. Build graph adjacency
        $adj = [];
        $inDegree = [];
        $nodesById = [];
        foreach ($rawNodes as $node) {
            $id = $node['id'];
            $nodesById[$id] = $node;
            $adj[$id] = [];
            $inDegree[$id] = 0;
        }

        foreach ($rawEdges as $edge) {
            $src = $edge['source'] ?? null;
            $tgt = $edge['target'] ?? null;
            if ($src && $tgt && isset($adj[$src])) {
                $adj[$src][] = $tgt;
                $inDegree[$tgt] = ($inDegree[$tgt] ?? 0) + 1;
            }
        }

        // 3. Compute hierarchical ranks (Topological layering with cycle/back-edge detection)
        $backEdges = [];
        $color = [];
        foreach ($nodesById as $id => $node) {
            $color[$id] = 0; // 0: unvisited, 1: visiting, 2: visited
        }

        $detectCycles = function ($u) use (&$detectCycles, &$color, $adj, &$backEdges) {
            $color[$u] = 1;
            foreach ($adj[$u] ?? [] as $v) {
                if (($color[$v] ?? 0) === 1) {
                    $backEdges["{$u}->{$v}"] = true;
                } elseif (($color[$v] ?? 0) === 0) {
                    $detectCycles($v);
                }
            }
            $color[$u] = 2;
        };

        foreach ($nodesById as $id => $node) {
            if ($color[$id] === 0) {
                $detectCycles($id);
            }
        }

        // Now compute ranks ignoring back-edges
        $rank = [];
        $queue = [];
        foreach ($nodesById as $id => $node) {
            if (($inDegree[$id] ?? 0) === 0 || ($node['type'] ?? '') === 'initial') {
                $rank[$id] = 0;
                $queue[] = $id;
            }
        }
        if (empty($queue) && !empty($nodesById)) {
            $firstId = array_key_first($nodesById);
            $rank[$firstId] = 0;
            $queue[] = $firstId;
        }

        $maxIterations = count($nodesById) * 4;
        $iter = 0;
        while (!empty($queue) && $iter < $maxIterations) {
            $iter++;
            $curr = array_shift($queue);
            $currRank = $rank[$curr] ?? 0;
            foreach ($adj[$curr] ?? [] as $neighbor) {
                // Ignore back-edges during ranking
                if (isset($backEdges["{$curr}->{$neighbor}"])) {
                    continue;
                }
                $newRank = $currRank + 1;
                if (!isset($rank[$neighbor]) || $newRank > $rank[$neighbor]) {
                    $rank[$neighbor] = $newRank;
                    $queue[] = $neighbor;
                }
            }
        }

        foreach ($nodesById as $id => $node) {
            if (!isset($rank[$id])) {
                $rank[$id] = 1;
            }
        }

        // 3b. Anti-collision in the same lane:
        // Ensure no two nodes in the same swimlane partition share the same rank!
        $changed = true;
        $antiCollisionGuard = 0;
        while ($changed && $antiCollisionGuard < 120) {
            $antiCollisionGuard++;
            $changed = false;

            $laneNodes = [];
            foreach ($nodesById as $id => $node) {
                $p = $node['partition'] ?? array_key_first($partitions);
                $laneNodes[$p][] = $id;
            }

            foreach ($laneNodes as $p => $ids) {
                usort($ids, function ($a, $b) use ($rank) {
                    return $rank[$a] <=> $rank[$b];
                });

                for ($i = 0; $i < count($ids) - 1; $i++) {
                    $u = $ids[$i];
                    $v = $ids[$i + 1];

                    if ($rank[$u] === $rank[$v]) {
                        // Collision detected! Push $v and all downstream nodes down by 1 rank
                        $bumpQueue = [$v];
                        $bumpVisited = [];
                        while (!empty($bumpQueue)) {
                            $curr = array_shift($bumpQueue);
                            if (isset($bumpVisited[$curr])) continue;
                            $bumpVisited[$curr] = true;
                            $rank[$curr]++;

                            foreach ($adj[$curr] ?? [] as $child) {
                                if (!isset($backEdges["{$curr}->{$child}"])) {
                                    if ($rank[$child] <= $rank[$curr]) {
                                        $bumpQueue[] = $child;
                                    }
                                }
                            }
                        }
                        $changed = true;
                        break 2;
                    }
                }
            }
        }

        $maxRank = 0;
        foreach ($rank as $r) {
            if ($r > $maxRank) {
                $maxRank = $r;
            }
        }

        // 4. Calculate Coordinates for Nodes
        $positionedNodes = [];
        foreach ($nodesById as $id => $node) {
            $pName = $node['partition'] ?? array_key_first($partitions);
            if (!isset($partitions[$pName])) {
                $pName = array_key_first($partitions);
            }
            $lane = $partitions[$pName];
            $r = $rank[$id];
            unset($x);

            $type = $node['type'] ?? 'activity';
            $dim = $this->calculateNodeDimensions($node);
            $w = $dim['width'];
            $h = $dim['height'];

            if ($type === 'fork') {
                // If fork splits into multiple swimlanes, span across the swimlanes involved
                $targets = $adj[$id] ?? [];
                $targetCenters = [];
                foreach ($targets as $tId) {
                    $tPart = $nodesById[$tId]['partition'] ?? $pName;
                    if (isset($partitions[$tPart])) {
                        $targetCenters[] = $partitions[$tPart]['x'] + ($partitions[$tPart]['width'] / 2);
                    }
                }
                $laneCx = $lane['x'] + ($lane['width'] / 2);
                $targetCenters[] = $laneCx;

                if (count($targetCenters) >= 2 && (max($targetCenters) - min($targetCenters) > 40)) {
                    $minCx = min($targetCenters);
                    $maxCx = max($targetCenters);
                    $w = ($maxCx - $minCx) + 120;
                    $h = 8;
                    $x = $minCx - 60;
                } else {
                    $w = 160;
                    $h = 8;
                    $x = $lane['x'] + ($lane['width'] / 2) - ($w / 2);
                }
            } elseif ($type === 'join') {
                // If join synchronizes multiple swimlanes, span across the swimlanes involved
                $sources = [];
                foreach ($adj as $uId => $children) {
                    if (in_array($id, $children)) {
                        $sources[] = $uId;
                    }
                }
                $sourceCenters = [];
                foreach ($sources as $sId) {
                    $sPart = $nodesById[$sId]['partition'] ?? $pName;
                    if (isset($partitions[$sPart])) {
                        $sourceCenters[] = $partitions[$sPart]['x'] + ($partitions[$sPart]['width'] / 2);
                    }
                }
                $laneCx = $lane['x'] + ($lane['width'] / 2);
                $sourceCenters[] = $laneCx;

                if (count($sourceCenters) >= 2 && (max($sourceCenters) - min($sourceCenters) > 40)) {
                    $minCx = min($sourceCenters);
                    $maxCx = max($sourceCenters);
                    $w = ($maxCx - $minCx) + 120;
                    $h = 8;
                    $x = $minCx - 60;
                } else {
                    $w = 160;
                    $h = 8;
                    $x = $lane['x'] + ($lane['width'] / 2) - ($w / 2);
                }
            }

            // Center node horizontally in its swimlane if not already set (e.g. for multi-lane fork/join)
            if (!isset($x)) {
                $x = $lane['x'] + ($lane['width'] / 2) - ($w / 2);
            }
            $y = 70 + ($r * $this->rankSep);

            $positionedNodes[$id] = array_merge($node, [
                'x' => (int)$x,
                'y' => (int)$y,
                'width' => (int)$w,
                'height' => (int)$h,
                'rank' => $r,
                'partition' => $pName,
                'lane_index' => $lane['index'],
            ]);
        }

        // Physical vertical clearance pass per swimlane
        foreach ($partitions as $pName => $pInfo) {
            $idsInLane = [];
            foreach ($positionedNodes as $id => $pNode) {
                if ($pNode['partition'] === $pName) {
                    $idsInLane[] = $id;
                }
            }
            usort($idsInLane, function ($a, $b) use ($positionedNodes) {
                return $positionedNodes[$a]['y'] <=> $positionedNodes[$b]['y'];
            });

            for ($i = 0; $i < count($idsInLane) - 1; $i++) {
                $uId = $idsInLane[$i];
                $vId = $idsInLane[$i + 1];
                $minY = $positionedNodes[$uId]['y'] + $positionedNodes[$uId]['height'] + 32;
                if ($positionedNodes[$vId]['y'] < $minY) {
                    $positionedNodes[$vId]['y'] = $minY;
                    $shiftQueue = [$vId];
                    $shiftVisited = [];
                    while (!empty($shiftQueue)) {
                        $curr = array_shift($shiftQueue);
                        if (isset($shiftVisited[$curr])) continue;
                        $shiftVisited[$curr] = true;
                        foreach ($adj[$curr] ?? [] as $childId) {
                            if (isset($positionedNodes[$childId]) && !isset($backEdges["{$curr}->{$childId}"])) {
                                $childMinY = $positionedNodes[$curr]['y'] + $positionedNodes[$curr]['height'] + 32;
                                if ($positionedNodes[$childId]['y'] < $childMinY) {
                                    $positionedNodes[$childId]['y'] = $childMinY;
                                    $shiftQueue[] = $childId;
                                }
                            }
                        }
                    }
                }
            }
        }

        $maxY = 0;
        foreach ($positionedNodes as $pn) {
            $bottom = $pn['y'] + $pn['height'];
            if ($bottom > $maxY) {
                $maxY = $bottom;
            }
        }

        $totalHeight = max(520, $maxY + 50);
        $totalWidth = max(720, 60 + (count($partitions) * $this->laneWidth));

        // Update partition bounds
        foreach ($partitions as $name => &$p) {
            $p['height'] = $totalHeight - 30;
            $p['y'] = 20;
        }

        // 5. Build Strict 90° Orthogonal Edges (Draw.io style Manhattan routing)
        $positionedEdges = [];
        foreach ($rawEdges as $idx => $edge) {
            $srcId = $edge['source'] ?? '';
            $tgtId = $edge['target'] ?? '';
            $guard = trim($edge['guard'] ?? '');

            if (isset($positionedNodes[$srcId], $positionedNodes[$tgtId])) {
                $src = $positionedNodes[$srcId];
                $tgt = $positionedNodes[$tgtId];

                $route = $this->calculateOrthogonalRoute($src, $tgt, $guard, $positionedNodes);

                $positionedEdges[] = array_merge($route, [
                    'id' => "edge_{$idx}",
                    'source' => $srcId,
                    'target' => $tgtId,
                    'guard' => $guard,
                ]);
            }
        }

        return [
            'width' => $totalWidth,
            'height' => $totalHeight,
            'partitions' => array_values($partitions),
            'nodes' => array_values($positionedNodes),
            'edges' => $positionedEdges,
        ];
    }

    /**
     * Calculate 90-degree orthogonal Manhattan waypoints
     */
    protected function calculateOrthogonalRoute(array $src, array $tgt, string $guard, array $allNodes): array
    {
        $sCx = $src['x'] + ($src['width'] / 2);
        $sCy = $src['y'] + ($src['height'] / 2);
        $tCx = $tgt['x'] + ($tgt['width'] / 2);
        $tCy = $tgt['y'] + ($tgt['height'] / 2);

        $sType = $src['type'] ?? 'activity';
        $tType = $tgt['type'] ?? 'activity';
        $isDecision = ($sType === 'decision');

        $guardLower = strtolower($guard);
        $isNegativeBranch = str_contains($guardLower, 'gagal') ||
                            str_contains($guardLower, 'batal') ||
                            str_contains($guardLower, 'cancel') ||
                            str_contains($guardLower, 'no') ||
                            str_contains($guardLower, 'tidak');

        $points = [];
        $labelPos = ['x' => 0, 'y' => 0];

        // Case A: Loopback / Backwards Flow (target is above source)
        if ($tgt['y'] < $src['y']) {
            $x1 = $src['x'];
            $y1 = $sCy;
            $gutterX = min($src['x'], $tgt['x']) - 35;
            $x2 = $tgt['x'];
            $y2 = $tCy;

            $points = [
                ['x' => $x1, 'y' => $y1],
                ['x' => $gutterX, 'y' => $y1],
                ['x' => $gutterX, 'y' => $y2],
                ['x' => $x2, 'y' => $y2],
            ];
            $labelPos = ['x' => $gutterX + 5, 'y' => ($y1 + $y2) / 2];
        }
        // Case B: Fork Node (splits cleanly straight down into each parallel target)
        elseif ($sType === 'fork') {
            $forkBottomY = $src['y'] + $src['height'];
            if ($tCx >= ($src['x'] - 15) && $tCx <= ($src['x'] + $src['width'] + 15)) {
                // Drop straight down from fork bar into target top
                $points = [
                    ['x' => $tCx, 'y' => $forkBottomY],
                    ['x' => $tCx, 'y' => $tgt['y']],
                ];
                $labelPos = ['x' => $tCx + 8, 'y' => ($forkBottomY + $tgt['y']) / 2];
            } else {
                $midY = $forkBottomY + (($tgt['y'] - $forkBottomY) / 2);
                $points = [
                    ['x' => $sCx, 'y' => $forkBottomY],
                    ['x' => $sCx, 'y' => $midY],
                    ['x' => $tCx, 'y' => $midY],
                    ['x' => $tCx, 'y' => $tgt['y']],
                ];
                $labelPos = ['x' => ($sCx + $tCx) / 2, 'y' => $midY - 8];
            }
        }
        // Case C: Target is a Join Node (parallel sources drop straight down into join bar)
        elseif ($tType === 'join') {
            $joinTopY = $tgt['y'];
            $srcBottomY = $src['y'] + $src['height'];
            if ($sCx >= ($tgt['x'] - 15) && $sCx <= ($tgt['x'] + $tgt['width'] + 15)) {
                // Drop straight down into the join bar
                $points = [
                    ['x' => $sCx, 'y' => $srcBottomY],
                    ['x' => $sCx, 'y' => $joinTopY],
                ];
                $labelPos = ['x' => $sCx + 8, 'y' => ($srcBottomY + $joinTopY) / 2];
            } else {
                $midY = $srcBottomY + (($joinTopY - $srcBottomY) / 2);
                $points = [
                    ['x' => $sCx, 'y' => $srcBottomY],
                    ['x' => $sCx, 'y' => $midY],
                    ['x' => $tCx, 'y' => $midY],
                    ['x' => $tCx, 'y' => $joinTopY],
                ];
                $labelPos = ['x' => ($sCx + $tCx) / 2, 'y' => $midY - 8];
            }
        }
        // Case D: Target is a Merge Node (reunites alternative branches into Left/Right vertices)
        elseif ($tType === 'merge') {
            $srcBottomY = $src['y'] + $src['height'];
            if ($sCx < $tCx - 35) {
                // Incoming from left: enter left point of diamond
                $points = [
                    ['x' => $sCx, 'y' => $srcBottomY],
                    ['x' => $sCx, 'y' => $tCy],
                    ['x' => $tgt['x'], 'y' => $tCy],
                ];
                $labelPos = ['x' => ($sCx + $tgt['x']) / 2, 'y' => $tCy - 8];
            } elseif ($sCx > $tCx + 35) {
                // Incoming from right: enter right point of diamond
                $points = [
                    ['x' => $sCx, 'y' => $srcBottomY],
                    ['x' => $sCx, 'y' => $tCy],
                    ['x' => $tgt['x'] + $tgt['width'], 'y' => $tCy],
                ];
                $labelPos = ['x' => ($sCx + $tgt['x'] + $tgt['width']) / 2, 'y' => $tCy - 8];
            } else {
                // Straight above: enter top vertex
                $points = [
                    ['x' => $sCx, 'y' => $srcBottomY],
                    ['x' => $tCx, 'y' => $tgt['y']],
                ];
                $labelPos = ['x' => $sCx + 8, 'y' => ($srcBottomY + $tgt['y']) / 2];
            }
        }
        // Case E: Decision Node Branching
        elseif ($isDecision) {
            if ($isNegativeBranch) {
                // Exit sideways (left or right)
                if ($tgt['x'] < $src['x']) {
                    // Go Left
                    $x1 = $src['x'];
                    $y1 = $sCy;
                    if (abs($sCy - $tCy) < 30) {
                        $points = [
                            ['x' => $x1, 'y' => $y1],
                            ['x' => $tgt['x'] + $tgt['width'], 'y' => $y1],
                        ];
                    } else {
                        $points = [
                            ['x' => $x1, 'y' => $y1],
                            ['x' => $tCx, 'y' => $y1],
                            ['x' => $tCx, 'y' => $tgt['y']],
                        ];
                    }
                    $labelPos = ['x' => $x1 - 15, 'y' => $y1 - 8];
                } else {
                    // Go Right
                    $x1 = $src['x'] + $src['width'];
                    $y1 = $sCy;
                    $points = [
                        ['x' => $x1, 'y' => $y1],
                        ['x' => $tCx, 'y' => $y1],
                        ['x' => $tCx, 'y' => $tgt['y']],
                    ];
                    $labelPos = ['x' => $x1 + 10, 'y' => $y1 - 8];
                }
            } else {
                // Positive branch (Down or Right)
                if (abs($sCx - $tCx) < 30) {
                    // Straight down
                    $points = [
                        ['x' => $sCx, 'y' => $src['y'] + $src['height']],
                        ['x' => $tCx, 'y' => $tgt['y']],
                    ];
                    $labelPos = ['x' => $sCx + 8, 'y' => $src['y'] + $src['height'] + 15];
                } else {
                    // Exit right or bottom then orthogonal step
                    $x1 = $src['x'] + $src['width'];
                    $y1 = $sCy;
                    if (abs($sCy - $tCy) < 30) {
                        $points = [
                            ['x' => $x1, 'y' => $y1],
                            ['x' => $tgt['x'], 'y' => $tCy],
                        ];
                    } else {
                        $points = [
                            ['x' => $x1, 'y' => $y1],
                            ['x' => $tCx, 'y' => $y1],
                            ['x' => $tCx, 'y' => $tgt['y']],
                        ];
                    }
                    $labelPos = ['x' => $x1 + 10, 'y' => $y1 - 8];
                }
            }
        }
        // Case F: Standard Forward Flow between Actions / Merge
        else {
            if (abs($sCx - $tCx) < 30) {
                // Same column: Pure straight vertical line
                $points = [
                    ['x' => $sCx, 'y' => $src['y'] + $src['height']],
                    ['x' => $tCx, 'y' => $tgt['y']],
                ];
                $labelPos = ['x' => $sCx + 8, 'y' => ($src['y'] + $src['height'] + $tgt['y']) / 2];
            } else {
                // Cross-swimlane: Orthogonal step
                if ($tgt['y'] <= $src['y'] + $src['height'] + 30) {
                    // Exit side directly
                    if ($tCx > $sCx) {
                        $points = [
                            ['x' => $src['x'] + $src['width'], 'y' => $sCy],
                            ['x' => $tgt['x'], 'y' => $tCy],
                        ];
                    } else {
                        $points = [
                            ['x' => $src['x'], 'y' => $sCy],
                            ['x' => $tgt['x'] + $tgt['width'], 'y' => $tCy],
                        ];
                    }
                    $labelPos = ['x' => ($sCx + $tCx) / 2, 'y' => $sCy - 8];
                } else {
                    // Exit bottom, step horizontally in middle, go down to target top
                    $midY = $src['y'] + $src['height'] + (($tgt['y'] - ($src['y'] + $src['height'])) / 2);
                    $points = [
                        ['x' => $sCx, 'y' => $src['y'] + $src['height']],
                        ['x' => $sCx, 'y' => $midY],
                        ['x' => $tCx, 'y' => $midY],
                        ['x' => $tCx, 'y' => $tgt['y']],
                    ];
                    $labelPos = ['x' => ($sCx + $tCx) / 2, 'y' => $midY - 8];
                }
            }
        }

        // Build SVG path data (all 'M x y L x y' - ZERO curves)
        $pathD = '';
        foreach ($points as $i => $pt) {
            $cmd = ($i === 0) ? 'M' : 'L';
            $pathD .= "{$cmd} {$pt['x']} {$pt['y']} ";
        }

        return [
            'points' => $points,
            'path_d' => trim($pathD),
            'label_pos' => $labelPos,
        ];
    }

    /**
     * Render authentic Draw.io manual export aesthetic:
     * - Pure white background
     * - Crisp black outlines (1.2px)
     * - Standard Swimlane table header
     * - Strict 90° orthogonal lines
     */
    public function renderSvg(array $layout): string
    {
        $w = $layout['width'] ?? 800;
        $h = $layout['height'] ?? 600;

        $svg = "<svg viewBox=\"0 0 {$w} {$h}\" width=\"{$w}\" height=\"{$h}\" xmlns=\"http://www.w3.org/2000/svg\" style=\"font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #ffffff; display: block; margin: 0 auto;\">\n";

        // Defs (Sharp Draw.io black arrowhead)
        $svg .= "  <defs>\n";
        $svg .= "    <marker id=\"arrow\" viewBox=\"0 0 10 10\" refX=\"9\" refY=\"5\" markerWidth=\"6\" markerHeight=\"6\" orient=\"auto\">\n";
        $svg .= "      <path d=\"M 0 1.5 L 9 5 L 0 8.5 z\" fill=\"#000000\" />\n";
        $svg .= "    </marker>\n";
        $svg .= "  </defs>\n\n";

        // 1. Draw Swimlanes (Standard Draw.io Table Header & Dividing Lines)
        $partitions = $layout['partitions'];
        $laneCount = count($partitions);

        if ($laneCount > 0) {
            $tableX = $partitions[0]['x'];
            $tableY = $partitions[0]['y'];
            $tableW = $laneCount * $this->laneWidth;
            $tableH = $partitions[0]['height'];
            $headerH = $this->headerHeight;

            // Outer Bounding Box
            $svg .= "  <!-- Swimlane Table Frame -->\n";
            $svg .= "  <rect x=\"{$tableX}\" y=\"{$tableY}\" width=\"{$tableW}\" height=\"{$tableH}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\" />\n";
            // Header Bottom Line
            $svg .= "  <line x1=\"{$tableX}\" y1=\"" . ($tableY + $headerH) . "\" x2=\"" . ($tableX + $tableW) . "\" y2=\"" . ($tableY + $headerH) . "\" stroke=\"#000000\" stroke-width=\"1.4\" />\n";

            // Vertical Dividers & Column Headers
            foreach ($partitions as $idx => $p) {
                $px = $p['x'];
                $pw = $p['width'];
                $pname = htmlspecialchars($p['name']);

                // Vertical column separator (except left edge)
                if ($idx > 0) {
                    $svg .= "  <line x1=\"{$px}\" y1=\"{$tableY}\" x2=\"{$px}\" y2=\"" . ($tableY + $tableH) . "\" stroke=\"#000000\" stroke-width=\"1.2\" />\n";
                }

                // Column Header Label
                $headerTextX = $px + ($pw / 2);
                $headerTextY = $tableY + ($headerH / 2) + 5;
                $svg .= "  <text x=\"{$headerTextX}\" y=\"{$headerTextY}\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"13\" font-weight=\"600\">{$pname}</text>\n";
            }
        }

        // 2. Draw Connectors / Lines (Strict 90° Orthogonal, Manhattan Routing)
        foreach ($layout['edges'] as $edge) {
            $pathD = $edge['path_d'];
            $guard = trim($edge['guard'] ?? '');

            // 90-degree orthogonal line
            $svg .= "  <path d=\"{$pathD}\" fill=\"none\" stroke=\"#000000\" stroke-width=\"1.2\" marker-end=\"url(#arrow)\" />\n";

            // Guard / Line Label
            if (!empty($guard)) {
                $guardText = htmlspecialchars($guard);
                $lx = $edge['label_pos']['x'];
                $ly = $edge['label_pos']['y'];

                // Wrap long guards into neat multi-line badges
                $guardLines = explode("\n", wordwrap($guardText, 22, "\n"));
                $badgeH = count($guardLines) * 14 + 8;
                $maxLineLen = 0;
                foreach ($guardLines as $gl) {
                    $maxLineLen = max($maxLineLen, strlen($gl));
                }
                $badgeW = max(40, $maxLineLen * 6.5 + 14);
                $bgX = $lx - ($badgeW / 2);
                $bgY = $ly - ($badgeH / 2);

                // Clean white badge with subtle border so lines never clash with text
                $svg .= "  <rect x=\"{$bgX}\" y=\"{$bgY}\" width=\"{$badgeW}\" height=\"{$badgeH}\" rx=\"4\" ry=\"4\" fill=\"#ffffff\" stroke=\"#94a3b8\" stroke-width=\"0.8\" />\n";
                $textStartY = $bgY + 12;
                foreach ($guardLines as $glIdx => $gl) {
                    $svg .= "  <text x=\"{$lx}\" y=\"" . ($textStartY + ($glIdx * 13)) . "\" text-anchor=\"middle\" fill=\"#0f172a\" font-size=\"10.5\" font-weight=\"500\">{$gl}</text>\n";
                }
            }
        }

        // 3. Draw Nodes (Standard Draw.io / UML Manual Shapes)
        foreach ($layout['nodes'] as $node) {
            $x = $node['x'];
            $y = $node['y'];
            $w = $node['width'];
            $h = $node['height'];
            $type = $node['type'] ?? 'activity';
            $label = htmlspecialchars($node['label'] ?? '');

            if ($type === 'initial') {
                $cx = $x + ($w / 2);
                $cy = $y + ($h / 2);
                $svg .= "  <!-- Initial Node (Solid Black Circle) -->\n";
                $svg .= "  <g><title>Initial Node (Titik Mulai)</title><circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"12\" fill=\"#000000\" stroke=\"#000000\" stroke-width=\"1\" /></g>\n";
                $svg .= "  <text x=\"{$cx}\" y=\"" . ($y - 6) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"bold\">Mulai</text>\n";
            } elseif ($type === 'final') {
                $cx = $x + ($w / 2);
                $cy = $y + ($h / 2);
                $svg .= "  <!-- Final Node (Bullseye) -->\n";
                $svg .= "  <g><title>Activity Final Node (Titik Selesai Alur)</title><circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"13\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\" /><circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"7.5\" fill=\"#000000\" /></g>\n";
                $svg .= "  <text x=\"{$cx}\" y=\"" . ($y + $h + 16) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"bold\">Selesai</text>\n";
            } elseif ($type === 'flow_final') {
                $cx = $x + ($w / 2);
                $cy = $y + ($h / 2);
                $svg .= "  <!-- Flow Final Node (Circle with X) -->\n";
                $svg .= "  <g><title>Flow Final Node (Penghenti Cabang Spesifik)</title><circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"13\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\" /><line x1=\"" . ($cx - 7) . "\" y1=\"" . ($cy - 7) . "\" x2=\"" . ($cx + 7) . "\" y2=\"" . ($cy + 7) . "\" stroke=\"#000000\" stroke-width=\"1.6\" /><line x1=\"" . ($cx + 7) . "\" y1=\"" . ($cy - 7) . "\" x2=\"" . ($cx - 7) . "\" y2=\"" . ($cy + 7) . "\" stroke=\"#000000\" stroke-width=\"1.6\" /></g>\n";
                $svg .= "  <text x=\"{$cx}\" y=\"" . ($y + $h + 16) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"10\" font-weight=\"bold\">Tutup Cabang</text>\n";
            } elseif ($type === 'decision' || $type === 'merge') {
                $cx = $x + ($w / 2);
                $cy = $y + ($h / 2);
                $d = "M {$cx} {$y} L " . ($x + $w) . " {$cy} L {$cx} " . ($y + $h) . " L {$x} {$cy} Z";
                $nodeTitle = ($type === 'decision') ? 'Decision Node (Percabangan Bersyarat - 1 In, >=2 Out dengan Guard)' : 'Merge Node (Penyatuan Alur Alternatif - >=2 In, 1 Out tanpa Guard)';
                $svg .= "  <!-- {$nodeTitle} -->\n";
                $svg .= "  <g><title>{$nodeTitle}</title><path d=\"{$d}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.2\" /></g>\n";
            } elseif ($type === 'fork' || $type === 'join') {
                $barTitle = ($type === 'fork') ? 'Fork Node (Pemisahan Alur Paralel/Simultan - 1 In, >=2 Out)' : 'Join Node (Sinkronisasi Alur Paralel/Simultan - >=2 In, 1 Out)';
                $svg .= "  <!-- {$barTitle} -->\n";
                $svg .= "  <g><title>{$barTitle}</title><rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" fill=\"#000000\" rx=\"2\" ry=\"2\" /></g>\n";
            } elseif ($type === 'send_signal') {
                $svg .= "  <!-- Send Signal Action (Pentagon Arrow) -->\n";
                $points = "{$x},{$y} " . ($x + $w - 14) . ",{$y} " . ($x + $w) . "," . ($y + ($h / 2)) . " " . ($x + $w - 14) . "," . ($y + $h) . " {$x}," . ($y + $h);
                $svg .= "  <g><title>Send Signal Action</title><polygon points=\"{$points}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.3\" /></g>\n";
                $svg .= "  <text x=\"" . ($x + ($w / 2) - 5) . "\" y=\"" . ($y + ($h / 2) + 4) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"500\">{$label}</text>\n";
            } elseif ($type === 'receive_signal') {
                $svg .= "  <!-- Accept Event / Receive Signal Action (Concave Arrow) -->\n";
                $points = "{$x},{$y} " . ($x + $w) . ",{$y} " . ($x + $w) . "," . ($y + $h) . " {$x}," . ($y + $h) . " " . ($x + 14) . "," . ($y + ($h / 2));
                $svg .= "  <g><title>Accept Event / Receive Signal Action</title><polygon points=\"{$points}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.3\" /></g>\n";
                $svg .= "  <text x=\"" . ($x + ($w / 2) + 5) . "\" y=\"" . ($y + ($h / 2) + 4) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"500\">{$label}</text>\n";
            } elseif ($type === 'object') {
                $svg .= "  <!-- Object Node (Data Store / Document) -->\n";
                $svg .= "  <g><title>Object Node (Data / Dokumen)</title><rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.3\" /></g>\n";
                $svg .= "  <text x=\"" . ($x + ($w / 2)) . "\" y=\"" . ($y + ($h / 2) + 4) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"500\">{$label}</text>\n";
            } elseif ($type === 'time_event') {
                $cx = $x + ($w / 2);
                $cy = $y + ($h / 2);
                $x2 = $x + $w;
                $y2 = $y + $h;
                $d = "M {$x} {$y} L {$x2} {$y} L {$cx} {$cy} L {$x2} {$y2} L {$x} {$y2} L {$cx} {$cy} Z";
                $svg .= "  <!-- Time Event (Hourglass - UML 2.5.1 Section 16.3 & Draw.io Stencil) -->\n";
                $svg .= "  <g><title>Time Event (Pemicu Waktu / Hourglass)</title><path d=\"{$d}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.3\" stroke-linejoin=\"round\" /></g>\n";
                $displayLabel = !empty($label) ? $label : 'Waktu Tunggu';
                $svg .= "  <text x=\"{$cx}\" y=\"" . ($y + $h + 15) . "\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"10.5\" font-weight=\"600\">{$displayLabel}</text>\n";
            } else {
                // Activity Action Node (Rounded Rectangle with White Fill and Black Border)
                $svg .= "  <!-- Action Node -->\n";
                $svg .= "  <rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" rx=\"10\" ry=\"10\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.3\" />\n";

                // Word wrap text inside node based on dynamic node width
                $wrapChars = max(18, (int)($w / 8.5));
                $lines = explode("\n", wordwrap($label, $wrapChars, "\n"));
                $lineCount = count($lines);
                $startY = $y + ($h / 2) - (($lineCount - 1) * 7.5) + 4;
                foreach ($lines as $lIdx => $line) {
                    $ly = $startY + ($lIdx * 15);
                    $svg .= "  <text x=\"" . ($x + ($w / 2)) . "\" y=\"{$ly}\" text-anchor=\"middle\" fill=\"#000000\" font-size=\"11\" font-weight=\"500\">{$line}</text>\n";
                }
            }
        }

        $svg .= "</svg>";
        return $svg;
    }
}
