<?php
/*
Plugin Name: IFF Membership Lookup
Description: Plugin for displaying the current licecend members of the IFF
Author: Scott O'Malley
Version: 1.0
*/
//------------------------------------------


function getActiveMemberships($formID, $pageSize)
{
    if (class_exists("GFForms")) {
        
        $paging      = array(
            'offset' => 0,
            'page_size' => (int)$pageSize
        );
        $sorting     = array(
            'key' => '19',
            'direction' => 'ASC'
        );
        $total_count = 0;
        
        $search_criteria = array(
            'field_filters' => array(
                'mode' => 'any',
                array(
                    'key' => 'payment_status',
                    'value' => 'Paid'
                ),
                array(
                    'key' => '7',
                    'value' => 'Youth Membership (€0.00)|0'
                ),
                array(
                    'key' => '7',
                    'value' => 'First Membership (€0.00)|0'
                )
            )
        );
        
        $unfilteredEntries = GFAPI::get_entries($formID, $search_criteria, $sorting, $paging, $total_count);
        $filteredEntries   = array();
        foreach ($unfilteredEntries as $value) {
            array_push($filteredEntries, filterEntry($value));
        }
        return $filteredEntries;
    }
    return null;
}

function filterEntry($value)
{
    return array(
        'prefix' => $value['1.2'],
        'firstName' => $value['1.3'],
        'lastName' => $value['1.6'],
        'club' => $value['9'],
        'nationality' => $value['28.6'],
        'iffNumber' => $value['19'],
        'handedness' => $value['12'],
        'membership' => humanReadableMembership($value['7'])
    );
}

function humanReadableMembership($value)
{
    $values = array(
        'Youth Membership (€0.00)|0' => 'Youth Membership',
        'Life Membership (€300.00)|300' => 'Life Membership',
        'Adult Membership (€30.00)|30' => 'Adult Membership',
        'Associate Membership (€5.00)|5' => 'Associate Membership',
        'First Membership (€0.00)|0' => 'First Membership',
        'Student Membership (€20.00)|20' => 'Student Membership'
    );
    
    return $values[$value];
}

function memberLookup($atts)
{
    
    $attributes = shortcode_atts(array(
        'formid' => '',
        'pagesize' => ''
    ), $atts);
    
    if (($attributes[formid] != '') && ($attributes[pagesize] != '')) {
        $entires = getActiveMemberships($attributes['formid'], $attributes['pagesize']);
        
        /* Turn on buffering */
        ob_start();
        ?>
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js"></script>
  <script>
    angular.module('lookup', []).controller('controller',  function ($scope) {
        $scope.members = <?php echo json_encode($entires); ?>;
    });    
  </script>
 
  <div ng-app="lookup" ng-controller="controller">
   <input type="text" ng-model="search" placeholder="Type IFF Number, Club, Name etc">
   <div class="pull-right">Showing {{ search.length >= 3 ? (members | filter:search).length : 0 }} of {{members.length}}</div>
   <table class="table table-bordered table-striped">
     <tr>
       <td>IFF Number</td>
       <td>Name</td>
       <td>Club</td>
       <td>Nationality</td>      
       <td>Handedness</td>      
       <td>Membership Type</td>      
     </tr>
      <tr ng-show="search.length >= 3" ng-repeat="m in members | filter:search">
       <td>{{m.iffNumber}}</td>
       <td>{{m.prefix}} {{m.firstName}} {{m.lastName}}</td>
       <td>{{m.club}}</td>
       <td>{{m.nationality}}</td>      
       <td>{{m.handedness}}</td>      
       <td>{{m.membership}}</td>      
     </tr>
   </table>     
  </div>
  
  <?php
        /* Get the buffered content into a var */
        $sc = ob_get_contents();
        
        /* Clean buffer */
        ob_end_clean();
        
        /* Return the content as usual */
        return $sc;
    } else {
        return new WP_Error("Error", __("FormID wasn't specified", "IFF_Lookup_Plugin"));
    }
}
add_shortcode('iff_membership_lookup', 'memberLookup');

?>