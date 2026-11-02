<?php

namespace App\Services;

class DrawioXmlService
{
    /**
     * Generate authentic diagrams.net (Draw.io) mxfile XML
     */
    public function generateXml(array $layout): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $mxfile = $doc->createElement('mxfile');
        $mxfile->setAttribute('host', 'ADAPt');
        $mxfile->setAttribute('modified', date('c'));
        $mxfile->setAttribute('agent', 'ADAPt-UML-Generator');
        $mxfile->setAttribute('version', '24.0.0');
        $mxfile->setAttribute('type', 'device');
        $doc->appendChild($mxfile);

        $diagram = $doc->createElement('diagram');
        $diagram->setAttribute('id', 'adapt-activity-diagram');
        $diagram->setAttribute('name', 'Activity Diagram');
        $mxfile->appendChild($diagram);

        $mxGraphModel = $doc->createElement('mxGraphModel');
        $mxGraphModel->setAttribute('dx', '1200');
        $mxGraphModel->setAttribute('dy', '800');
        $mxGraphModel->setAttribute('grid', '1');
        $mxGraphModel->setAttribute('gridSize', '10');
        $mxGraphModel->setAttribute('guides', '1');
        $mxGraphModel->setAttribute('tooltips', '1');
        $mxGraphModel->setAttribute('connect', '1');
        $mxGraphModel->setAttribute('arrows', '1');
        $mxGraphModel->setAttribute('fold', '1');
        $mxGraphModel->setAttribute('page', '1');
        $mxGraphModel->setAttribute('pageScale', '1');
        $mxGraphModel->setAttribute('pageWidth', (string)($layout['width'] + 100));
        $mxGraphModel->setAttribute('pageHeight', (string)($layout['height'] + 100));
        $mxGraphModel->setAttribute('math', '0');
        $mxGraphModel->setAttribute('shadow', '0');
        $diagram->appendChild($mxGraphModel);

        $root = $doc->createElement('root');
        $mxGraphModel->appendChild($root);

        // Cell 0 and Cell 1 default layers
        $cell0 = $doc->createElement('mxCell');
        $cell0->setAttribute('id', '0');
        $root->appendChild($cell0);

        $cell1 = $doc->createElement('mxCell');
        $cell1->setAttribute('id', '1');
        $cell1->setAttribute('parent', '0');
        $root->appendChild($cell1);

        // 1. Partitions (Swimlanes) - Clean Draw.io format, unblocked for free editing
        foreach ($layout['partitions'] as $idx => $p) {
            $laneId = "lane_{$idx}";

            $cell = $doc->createElement('mxCell');
            $cell->setAttribute('id', $laneId);
            $cell->setAttribute('value', $p['name']);
            // container=0 and pointerEvents=0 ensure cells inside can be freely selected, moved, and edited without feeling locked
            $cell->setAttribute('style', 'swimlane;whiteSpace=wrap;html=1;startSize=34;horizontal=1;fillColor=none;strokeColor=#000000;strokeWidth=1.4;fontColor=#000000;fontFamily=Helvetica;fontSize=13;fontStyle=1;collapsible=0;container=0;pointerEvents=0;connectable=0;');
            $cell->setAttribute('vertex', '1');
            $cell->setAttribute('parent', '1');

            $geo = $doc->createElement('mxGeometry');
            $geo->setAttribute('x', (string)$p['x']);
            $geo->setAttribute('y', (string)$p['y']);
            $geo->setAttribute('width', (string)$p['width']);
            $geo->setAttribute('height', (string)$p['height']);
            $geo->setAttribute('as', 'geometry');
            $cell->appendChild($geo);

            $root->appendChild($cell);
        }

        // 2. Nodes
        foreach ($layout['nodes'] as $node) {
            $nodeId = $node['id'];
            $type = $node['type'] ?? 'activity';
            $label = $node['label'] ?? '';

            // Rule 1: Structural nodes (decision, merge, fork, join, flow_final) in UML must NEVER have text inside the shape
            if ($type === 'decision' || $type === 'merge' || $type === 'fork' || $type === 'join' || $type === 'flow_final') {
                $label = '';
            }

            $style = $this->getStyleForType($type);

            $cell = $doc->createElement('mxCell');
            $cell->setAttribute('id', $nodeId);
            $cell->setAttribute('value', $label);
            $cell->setAttribute('style', $style);
            $cell->setAttribute('vertex', '1');
            $cell->setAttribute('parent', '1');

            $geo = $doc->createElement('mxGeometry');
            $geo->setAttribute('x', (string)$node['x']);
            $geo->setAttribute('y', (string)$node['y']);
            $geo->setAttribute('width', (string)$node['width']);
            $geo->setAttribute('height', (string)$node['height']);
            $geo->setAttribute('as', 'geometry');
            $cell->appendChild($geo);

            $root->appendChild($cell);
        }

        // Build node index for connection anchor calculation
        $nodesById = [];
        foreach ($layout['nodes'] as $node) {
            $nodesById[$node['id']] = $node;
        }

        // 3. Edges - Strict 90° Orthogonal Lines with Precise Draw.io Connection Anchors
        foreach ($layout['edges'] as $idx => $edge) {
            $edgeId = "edge_{$idx}";
            $guard = trim($edge['guard'] ?? '');

            // Rule 2: In standard UML, guard condition is formatted with brackets [kondisi]
            $displayGuard = '';
            if (!empty($guard)) {
                $displayGuard = str_starts_with($guard, '[') ? $guard : "[{$guard}]";
            }

            $srcId = $edge['source'] ?? '';
            $tgtId = $edge['target'] ?? '';
            $s = $nodesById[$srcId] ?? null;
            $t = $nodesById[$tgtId] ?? null;

            // Draw.io dynamic orthogonal edge style with precision anchors
            $styleParams = [
                'edgeStyle=orthogonalEdgeStyle',
                'rounded=0',
                'orthogonalLoop=1',
                'jettySize=auto',
                'html=1',
                'strokeColor=#000000',
                'strokeWidth=1.2',
                'fontColor=#000000',
                'fontSize=11',
                'fontFamily=Helvetica',
                'labelBackgroundColor=#ffffff',
            ];

            if ($s) {
                $sType = $s['type'] ?? 'activity';
                if ($sType === 'fork') {
                    if ($t) {
                        $tCx = $t['x'] + ($t['width'] / 2);
                        $frac = ($tCx - $s['x']) / max(1, $s['width']);
                        $frac = round(max(0.05, min(0.95, $frac)), 2);
                        $styleParams[] = "exitX={$frac}";
                        $styleParams[] = "exitY=1";
                        $styleParams[] = "exitDx=0";
                        $styleParams[] = "exitDy=0";
                    }
                } elseif ($sType === 'decision') {
                    $guardLower = strtolower($guard);
                    $isNegative = str_contains($guardLower, 'gagal') ||
                                  str_contains($guardLower, 'batal') ||
                                  str_contains($guardLower, 'tidak') ||
                                  str_contains($guardLower, 'no') ||
                                  str_contains($guardLower, 'habis') ||
                                  str_contains($guardLower, 'salah') ||
                                  str_contains($guardLower, 'sebaliknya');
                    if ($isNegative) {
                        if ($t && $t['x'] < $s['x']) {
                            $styleParams[] = "exitX=0";
                            $styleParams[] = "exitY=0.5";
                            $styleParams[] = "exitDx=0";
                            $styleParams[] = "exitDy=0";
                        } else {
                            $styleParams[] = "exitX=1";
                            $styleParams[] = "exitY=0.5";
                            $styleParams[] = "exitDx=0";
                            $styleParams[] = "exitDy=0";
                        }
                    } else {
                        $styleParams[] = "exitX=0.5";
                        $styleParams[] = "exitY=1";
                        $styleParams[] = "exitDx=0";
                        $styleParams[] = "exitDy=0";
                    }
                } elseif ($sType === 'merge') {
                    $styleParams[] = "exitX=0.5";
                    $styleParams[] = "exitY=1";
                    $styleParams[] = "exitDx=0";
                    $styleParams[] = "exitDy=0";
                }
            }

            if ($t) {
                $tType = $t['type'] ?? 'activity';
                if ($tType === 'join') {
                    if ($s) {
                        $sCx = $s['x'] + ($s['width'] / 2);
                        $frac = ($sCx - $t['x']) / max(1, $t['width']);
                        $frac = round(max(0.05, min(0.95, $frac)), 2);
                        $styleParams[] = "entryX={$frac}";
                        $styleParams[] = "entryY=0";
                        $styleParams[] = "entryDx=0";
                        $styleParams[] = "entryDy=0";
                    }
                } elseif ($tType === 'merge') {
                    if ($s) {
                        $sCx = $s['x'] + ($s['width'] / 2);
                        $tCx = $t['x'] + ($t['width'] / 2);
                        if ($sCx < $tCx - 35) {
                            $styleParams[] = "entryX=0";
                            $styleParams[] = "entryY=0.5";
                            $styleParams[] = "entryDx=0";
                            $styleParams[] = "entryDy=0";
                        } elseif ($sCx > $tCx + 35) {
                            $styleParams[] = "entryX=1";
                            $styleParams[] = "entryY=0.5";
                            $styleParams[] = "entryDx=0";
                            $styleParams[] = "entryDy=0";
                        } else {
                            $styleParams[] = "entryX=0.5";
                            $styleParams[] = "entryY=0";
                            $styleParams[] = "entryDx=0";
                            $styleParams[] = "entryDy=0";
                        }
                    }
                }
            }

            $cell = $doc->createElement('mxCell');
            $cell->setAttribute('id', $edgeId);
            $cell->setAttribute('value', $displayGuard);
            $cell->setAttribute('style', implode(';', $styleParams) . ';');
            $cell->setAttribute('edge', '1');
            $cell->setAttribute('parent', '1');
            $cell->setAttribute('source', $edge['source']);
            $cell->setAttribute('target', $edge['target']);

            $geo = $doc->createElement('mxGeometry');
            $geo->setAttribute('relative', '1');
            $geo->setAttribute('as', 'geometry');
            $cell->appendChild($geo);

            $root->appendChild($cell);
        }

        return $doc->saveXML();
    }

    /**
     * Check if XML is syntactically well-formed and valid diagrams.net structure
     */
    public function isValidXml(string $xmlContent): bool
    {
        if (empty(trim($xmlContent))) {
            return false;
        }

        libxml_use_internal_errors(true);
        $sxml = simplexml_load_string($xmlContent);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if ($sxml === false || !empty($errors)) {
            return false;
        }

        return isset($sxml->diagram);
    }

    /**
     * Clean Draw.io / UML black and white manual styles
     */
    protected function getStyleForType(string $type): string
    {
        return match ($type) {
            'initial' => 'ellipse;html=1;shape=startState;fillColor=#000000;strokeColor=#000000;verticalLabelPosition=top;verticalAlign=bottom;labelPosition=center;align=center;fontFamily=Helvetica;fontSize=11;fontColor=#000000;',
            'final' => 'ellipse;html=1;shape=endState;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.4;verticalLabelPosition=bottom;verticalAlign=top;labelPosition=center;align=center;fontFamily=Helvetica;fontSize=11;fontColor=#000000;',
            'flow_final' => 'ellipse;html=1;shape=mxgraph.sysml.flowFinal;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.4;verticalLabelPosition=bottom;verticalAlign=top;labelPosition=center;align=center;fontFamily=Helvetica;fontSize=11;fontColor=#000000;',
            'decision', 'merge' => 'rhombus;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.2;fontColor=#000000;fontFamily=Helvetica;fontSize=11;',
            'fork', 'join' => 'html=1;points=[];perimeter=orthogonalPerimeter;fillColor=#000000;strokeColor=none;rounded=0;',
            'time_event' => 'shape=mxgraph.sysml.timeEvent;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.2;fontColor=#000000;fontFamily=Helvetica;fontSize=11;verticalLabelPosition=bottom;verticalAlign=top;align=center;',
            'send_signal' => 'shape=mxgraph.sysml.sendSignal;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.2;fontColor=#000000;fontFamily=Helvetica;fontSize=11;',
            'receive_signal' => 'shape=mxgraph.sysml.acceptEvent;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.2;fontColor=#000000;fontFamily=Helvetica;fontSize=11;',
            'object' => 'whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.2;fontColor=#000000;fontFamily=Helvetica;fontSize=11;',
            default => 'rounded=1;whiteSpace=wrap;html=1;arcSize=24;fillColor=#ffffff;strokeColor=#000000;strokeWidth=1.4;fontColor=#000000;fontFamily=Helvetica;fontSize=12;align=center;verticalAlign=middle;spacing=6;',
        };
    }
}
