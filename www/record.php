<?php

$wfo = $path_parts[0];

$record = new TaxonRecord($wfo . '-' . WFO_DEFAULT_VERSION); 

$page_title = $record->getFullNameStringPlain();


require_once('header.php');
?>

<div class="container-lg">
    <form role="form" method="GET" action="search">
        <div class="row">
            <!-- main content -->
            <div class="col">
                <div data-bs-toggle="offcanvas" style="float: right;">
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasResponsive"
                        aria-controls="offcanvasResponsive">Classification</button>
                </div>

                <?php
    echo '<p style="margin-bottom: 0.5em;">';

    // description of record type
    switch ($record->getRole()) {
        case 'accepted':
            if(strpos($record->getFullNameStringPlain(), '×') !== false) $desc = "Accepted hybrid " . $record->getRank();
            else $desc = "Accepted " . $record->getRank();
            $colour = 'green';
            break;
        case 'synonym':
            $desc = "Synonymous " . $record->getRank() . " name";
            $colour = 'blue';
            break;
        case 'unplaced':
            $desc = "Unplaced " . $record->getRank() . " name";
            $colour = 'black';
            break;
        case 'deprecated':
            $desc = "Deprecated " . $record->getRank() . " name";
            $colour = 'red';
            break;
        default:
            $desc = "";
            $colour = 'black';
            break;
    }
    
    echo '<span style="margin-bottom: 0px;">';
    echo "<span class=\"fw-bold\" style=\"color: $colour; margin-bottom: 0px;\">$desc:</span>&nbsp;";

    // WFO ID Linking
    echo '<span
            class="fw-bold"
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            title="Click to copy persistent URL to clipboard." 
            onclick="navigator.clipboard.writeText(\'https://list.worldfloraonline.org/'. $record->getWfoId() .'\')" 
            />';
    echo $record->getWfoId();
    echo '</span>';

    echo '</p>'; // header p
    
    // header
    echo "<h1 style=\" position: relative;\">{$record->getFullNameStringHtml()}";



    echo "</h1>";
    echo "<p>{$record->getCitationMicro()}</p>";
    
    // link to accepted name
    if($record->getRole() == 'synonym'){
        $accepted = new TaxonRecord($record->getAcceptedId());
        echo '<p class="fw-bold fs-4" >Correct name: ';
        echo "<a href=\"{$accepted->getWfoId()}\">{$accepted->getFullNameStringHtml()}</a>";
        echo '</p>';
    }

    // Synonyms
    render_name_list($record->getSynonyms(), $record, "Synonyms", "Other names that are placed in this taxon but that are not the formally accepted name of this taxon.");

    // attributes (facets)
    $facets = $record->getFacets();
    if($facets){
        
        echo '<div class="card">';
        echo '<div class="card-header">';
         echo '<span
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Features of this taxon. Number of sources in brackets. Click for provenance details." >Taxon Attributes</span>&nbsp;';
        echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($facets), 0)  .'</span> </div>';
        echo '<ul class="list-group  list-group-flush" style="max-height: 10em; overflow: auto;">';

        foreach($facets as $f){
            echo '<li class="list-group-item  list-group-item-action">';
            echo "<strong>{$f->name}: </strong>";
            $spacer = '';
            foreach($f->facet_values as $fv){
                echo $spacer;
                $spacer = '; ';
                echo '<span>';
                if($fv->link){
                    echo "<a target=\"facet_info\" href=\"{$fv->link}\">{$fv->name}</a>";
                }else{
                    echo $fv->name;
                }

                $prov_json = urlencode(json_encode($fv->provenance));

                echo '<strong data-bs-toggle="modal" data-bs-target="#provModal" data-wfoprov="'. $prov_json .'" style="cursor: zoom-in;"> ('. count($fv->provenance) .')</strong>';
                echo '</span>';
               
            } // end facet value

            echo '</li>';
    
        } // end facet

       echo '</ul>'; // end list group
       echo '</div>'; // end card

    }

    // references    
    render_references($record->getNomenclaturalReferences(), 'Nomenclatural Resources', "Links to information useful for understanding the nomenclature of this name.");
    render_references($record->getTaxonomicReferences(), 'Taxonomic Sources', "Links information supporting the taxonomy accepted here.");
    render_references($record->getTreatmentReferences(), 'Other Treatments', "Other occurrences of this name that may be useful, including alternative taxonomic views.");

    // unplaced names
    render_name_list($record->getUnplacedNames(), $record, "Unplaced Names", "Names that experts have not yet placed in the classification.");


?>
            </div>
            <!-- Classificaton -->
            <div class="col-4 offcanvas-lg offcanvas-end" tabindex="-1" id="offcanvasResponsive"
                aria-labelledby="offcanvasResponsiveLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasResponsiveLabel">Classification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                        data-bs-target="#offcanvasResponsive" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">

                    <div class="row" style="width: 100%">
                        <div class="col">
                            <div class="card" style="width: 100%">
                                <?php
                            
                            // taxonomic placement

                            // header
                            echo '<div class="card-header">';
                            if($record->getRole() == 'unplaced'){
                                    echo '<span
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="left"
                                    title="The genus part of the names suggests it could occur here in the classification, if it isn\'t synonymised." >Potential Placement</span>&nbsp;';
                            }else{
                                echo '<span
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="left"
                                    title="The position of the taxon within the classification." >Placement</span>&nbsp;';
                            }
                            
                            
                            // list depends on role
                            switch ($record->getRole()) {

                                // placement of a synonym
                                case 'synonym':
                                $accepted = new TaxonRecord($record->getAcceptedId());
                                $ancestors = $accepted->getPath(); // get the path to the root
                                $ancestors = array_reverse($ancestors); // reverse order
                                array_shift($ancestors); // remove 'code'
                                
                                // add badge
                                echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($ancestors), 0)  . '</span>';
                                echo '</div>'; // end header
                                echo '<div class="list-group  list-group-flush">'; // start list

                                // write in the path to the accepted name
                                render_ancestors($ancestors, false);

                                // add in self as a synonym
                                echo "<a href=\"{$record->getWfoId()}\" class=\"list-group-item list-group-item-action
                                    disabled\">";
                                    echo '<div class="row gx-1">';
                                        echo '<div class="col-4 text-end" style="font-size:90%">synonym:</div>';
                                        echo '<div class="col text-start fw-bold">' .
                                            $record->getFullNameStringNoAuthorsHtml() . '</div>';
                                        echo '</div>'; // end row
                                    echo "</a>";

                                break;

                                // Unplaced - we try and find something!
                                case 'unplaced':
                                    $candidates = $record->getAssociatedGenusNames();
                                    if($candidates){

                                    if(count($candidates) == 1 && $candidates[0]->getRole() == 'accepted'){
                                        $candidate = new TaxonRecord($candidates[0]->getId() . '-'. WFO_DEFAULT_VERSION); // convert to taxon object
                                        $ancestors = $candidate->getPath(); // get the path to the root
                                        $ancestors = array_reverse($ancestors); // reverse order
                                        array_shift($ancestors); // remove 'code'
                                        
                                        // add badge
                                        echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($ancestors), 0)  . '</span>';
                                        echo '</div>'; // end header
                                        echo '<div class="list-group  list-group-flush">'; // start list
                                        render_ancestors($ancestors, false); // write it out

                                    }else{
                                        
                                        // add badge
                                        echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($candidates), 0)  . '</span>';
                                        echo '</div>'; // end header
                                        echo '<div class="list-group  list-group-flush">'; // start list
                                        render_ancestors($candidates, false);
                                    }

                                    }else{
                                        // no badge
                                        echo '</div>'; // end header
                                        echo '<div class="list-group  list-group-flush">'; // start list   
                                        echo "<a class=\"list-group-item list-group-item-action disabled\">Unknown</a>";
                                    }
                                    break;

                                case 'deprecated':
                                    echo '</div>'; // end header
                                    echo '<div class="list-group  list-group-flush">'; // start list  
                                    echo "<a class=\"list-group-item list-group-item-action disabled\">Deprecated names will not be placed in the classification.</a>";
                                    break;

                                // by default we treat it like it is accepted
                                default:
                                    $ancestors = $record->getPath(); // get the path to the root
                                    $ancestors = array_reverse($ancestors); // reverse order
                                    array_shift($ancestors); // remove 'code'

                                    // badge
                                    echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($ancestors), 0)  . '</span>';
                                    echo '</div>'; // end header
                                    echo '<div class="list-group  list-group-flush">'; // start list
                                    render_ancestors( $ancestors, true);
                                    break;

                            } // end switch
                                ?>
                            </div>
                        </div>
                        <?php

                        // children
                        $kids = $record->getChildren();
                        if($kids){
                            echo '<div class="card" style="width: 100%">';
                            echo '<div class="card-header">';
                             echo '<span
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="left"
                                    title="Direct descendants of this taxon." >Child Taxa</span>&nbsp;';
                            echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($kids), 0)  . '</span>  </div>';
                            echo '<div class="list-group  list-group-flush" style="max-height: 20em; overflow: auto;">';

                            for($i = 0; $i < count($kids); $i++){
                                $kid = $kids[$i];
                                echo "<a href=\"{$kid->getWfoId()}\" class=\"list-group-item  list-group-item-action\">{$kid->getFullNameStringHtml()}</a>";
                            }

                            echo '</div>'; // end list
                            echo '</div>'; // end card
                        
                        }

                        // Do we have siblings?
                        $parent = $record->getParent();
                        if($parent){
                            $siblings = $parent->getChildren();
                            if(count($siblings) > 1){

                                echo '<div class="card" style="width: 100%">';
                                echo '<div class="card-header">';
                                echo '<span
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="left"
                                        title="Taxa at the same level in the classification." >Sibling Taxa</span>&nbsp;';
                                echo '<span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($siblings), 0)  . '</span>  </div>';
                                echo '<div class="list-group  list-group-flush" style="max-height: 20em; overflow: auto;">';

                                for($i = 0; $i < count($siblings); $i++){
                                    $bro = $siblings[$i];
                                    $disabled = $bro->getId() == $record->getId() ? 'disabled' : '';
                                    echo "<a href=\"{$bro->getWfoId()}\" class=\"list-group-item  list-group-item-action $disabled\" >{$bro->getFullNameStringHtml()}</a>";
                                }

                                echo '</div>'; // end list
                                echo '</div>'; // end card

                            } // has more than one sibling
                        } // has parent
                            
                                      
                        ?>
                    </div> <!-- end col -->
                </div><!-- end row -->

            </div>
        </div>
</div>

</div>
</form>
</div>

<?php
require_once('footer.php');

function render_references($refs_all, $title, $help = ''){

    // filter out undesirables
    $refs = array();
    foreach($refs_all as $ref){
        // we don't render the old plantlist links
        if(strpos($ref->uri, 'theplantlist.org')) continue;
        $refs[] = $ref;
    }

    // render nothing if we have nothing
    if(count($refs) == 0) return;
    
    // render the card
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<span
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="'. $help .'" >';
    echo $title;
    echo '</span>';
    echo ' <span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($refs), 0)  .'</span> </div>';
    echo '<div class="list-group  list-group-flush" style="max-height: 20em; overflow: auto;">';
    
    // people first
    foreach($refs as $ref){
        if ($ref->kind != 'person') continue;
        render_reference($ref);
    }
    // literature
    foreach($refs as $ref){
        if ($ref->kind != 'literature') continue;
        render_reference($ref);
    }
    // specimens
    foreach($refs as $ref){
        if ($ref->kind != 'specimen') continue;
        render_reference($ref);
    }
    // database
    foreach($refs as $ref){
        if ($ref->kind != 'database') continue;
        render_reference($ref);
    }

    echo '</div>'; // end list group
    echo '</div>'; // end card

}

function render_reference($ref){


    echo "<a href=\"{$ref->uri}\" class=\"list-group-item  list-group-item-action\" target=\"{$ref->kind}\">";
    echo '<div class="row">';

    echo '<div class="col-1">'; 
    if($ref->thumbnailUri){
        echo "<img src=\"$ref->thumbnailUri\" width=\"50px\" />";
    }else{
        switch ($ref->kind) {
            case 'database':
                echo '<img src="../images/database.png" width="50px" />';
                break;
            case 'person':
                echo '<img src="../images/person.png" width="50px" style="margin-top: 0.5em"/>';
                break;
            case 'literature':
                echo '<img src="../images/literature.png" width="50px" style="margin-top: 0.5em"/>';
                break;
            case 'specimen':
                echo '<img src="../images/literature.png" width="50px" style="margin-top: 0.5em"/>';
                break;
            default:
                echo '<div width="50px" >&nbsp;</div>';
                break;
        }
    }
    echo '</div>';
    echo '<div class="col">';
    echo "<p><span class=\"fw-bold\">$ref->label</span><br/>$ref->comment</p>";
    echo '</div>';
    echo '</div>'; // end row
    echo "</a>";
}

function render_ancestors($ancestors, $disable_last = true){

            for($i = 0; $i < count($ancestors); $i++){
                $anc = $ancestors[$i];
                $disabled = $i == count($ancestors) - 1 &&  $disable_last ? 'disabled' : '';

                echo "<a href=\"{$anc->getWfoId()}\" class=\"list-group-item  list-group-item-action $disabled\">";
                echo '<div class="row gx-1">';
                echo '<div class="col-4 text-end" style="font-size:90%">' . $anc->getRank() . ':</div>';
                echo '<div class="col text-start fw-bold">' . $anc->getFullNameStringNoAuthorsHtml() . '</div>';
                echo '</div>'; // end row
                echo "</a>";
            }
}


function render_name_list($names, $record, $title, $help){

    if(!$names) return;

    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<span
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="'. $help .'" >';
    echo $title;
    echo '</span>';
    echo ' <span class="badge rounded-pill text-bg-success" style="font-size: 70%; vertical-align: super;">'. number_format(count($names), 0)  .'</span> </div>';
    echo '<div class="list-group  list-group-flush" style="max-height: 10em; overflow: auto;">';
    for($i = 0; $i < count($names); $i++){
        $n = $names[$i];

        // is this homotypic with the current record?
        if($n->getBasionymWfoId() == $record->getWfoId() || $record->getBasionymWfoId() == $n->getWfoId()){
            $status = "<span class=\"fw-bold\">[{$n->getNomenclaturalStatus()} : homotypic]</span>";
        }else{
            $status = "<span class=\"fw-bold\">[{$n->getNomenclaturalStatus()}]</span>";
        }

        echo "<a href=\"{$n->getWfoId()}\" class=\"list-group-item  list-group-item-action\">{$n->getFullNameStringHtml()} $status</a>";
    }
    echo '</div>'; // end list group
    echo '</div>'; // end card

}

?>