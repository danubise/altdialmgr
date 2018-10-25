<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 07.10.18
 * Time: 4:05
 */
//printarray($restartStatus);

?>
<form method="post"  enctype="multipart/form-data" action="<?=baseurl('service/setparam')?>">

<table >
    <tr>
        <td>

            <table>
                <tr>
                    <th>Available Queues</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Active Queues</th>
                </tr>
                <tr>
                    <td>
                        <p><select size="10" multiple name="queuesAvailable[]" style="width: 150px">
                                <?php
                                foreach ($queueList as $key=>$queueExten){
                                    echo "<option value=\"".$queueExten."\">$queueExten</option>";
                                }
                                ?>
                            </select></p>
                    </td>
                    <td style="width: 20px">
                        &nbsp
                    </td>
                    <td>
                        <button name="action" value="add" class="btn btn-primary">===></button>
                        <br>
                        <button name="action" value="del" class="btn btn-primary"><===</button>
                    </td>
                    <td style="width: 20px">
                        &nbsp
                    </td>
                    <td>
                        <p><select size="10" multiple name="queuesActive[]"  style="width: 150px">
                                <?php
                                foreach ($queueListActive    as $key=>$queueExten){
                                    echo "<option value=\"".$queueExten."\">$queueExten</option>";
                                }
                                ?>
                            </select></p>
                    </td>
                </tr>
            </table>


        </td>
        <td style="width: 20px">
            &nbsp
        </td>
        <td style="text-align: left; vertical-align: top;padding: 0">
            <table>
                <tr>
                    <th>
                        Current configuration
                    </th>
                </tr>
                <tr>
                    <td>
                        <p>Queues:</p>
                        <ul>
                            <?php
                            if(isset($queueListCurrent) && is_array($queueListCurrent)) {
                                foreach ($queueListCurrent as $key => $queueExten) {
                                    echo "<li>" . $queueExten . "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 20px">
            &nbsp
        </td>
        <td style="text-align: left; vertical-align: top;padding: 0">
            <table>
                <tr>
                    <th>
                        Other settings
                    </th>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="activatelog" id="activatelog"  value="1" <?php
                        if($activatelog==1){
                            echo " checked";
                        }
                        ?>
                        >&nbsp enable logging
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <button name="action" value="submit" class="btn btn-primary">Submit</button>
                    </td>
                    <td style="width: 20px">
                        &nbsp
                    </td>
                    <td>
                        <a href="<?=baseurl('service/activate')?>" class="btn btn-success
                        <?php
                        if($restartStatus == 1) {
                            echo " disabled";
                        }
                        ?>">Activate</a>
                    </td>
                    <td style="width: 20px">
                        &nbsp
                    </td>
                    <td>
                        Restart "eventm" service
                        <?php
                        if($restartStatus == 0) {
                            echo "<b>done</b>";
                        }else{
                            echo "<b>in progress</b>";
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</form>