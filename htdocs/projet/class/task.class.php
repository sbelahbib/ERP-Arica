<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/projet/class/task.class.php
 *      \ingroup    project
 *      \brief      This file is a CRUD class file for Task (Create/Read/Update/Delete)
 *		\version    $Id: task.class.php,v 1.12 2011/07/31 23:23:39 eldy Exp $
 *		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *      \class      Task
 *      \brief      Class to manage tasks
 *		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
 */
class Task extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='project_task';		//!< Id that identify managed objects
	var $table_element='projet_task';	//!< Name of table without prefix where object is stored

    var $id;

	var $fk_project;
	var $fk_task_parent;
	var $label;
	var $description;
	var $duration_effective;
	var $date_c;
	var $date_start;
	var $date_end;
	var $progress;
	var $priority;
	var $fk_user_creat;
	var $fk_user_valid;
	var $statut;
	var $note_private;
	var $note_public;

	var $timespent_id;
	var $timespent_duration;
	var $timespent_old_duration;
	var $timespent_date;
	var $timespent_fk_user;
	var $timespent_note;


    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Task($DB)
    {
        $this->db = $DB;
        return 1;
    }


    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (";
		$sql.= "fk_projet";
		$sql.= ", fk_task_parent";
		$sql.= ", label";
		$sql.= ", description";
		$sql.= ", datec";
		$sql.= ", fk_user_creat";
		$sql.= ", dateo";
		$sql.= ", datee";
		$sql.= ", progress";
        $sql.= ") VALUES (";
		$sql.= $this->fk_project;
		$sql.= ", ".$this->fk_task_parent;
		$sql.= ", '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->description)."'";
		$sql.= ", '".$this->db->idate($this->date_c)."'";
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_start!=''?"'".$this->db->idate($this->date_start)."'":'null');
		$sql.= ", ".($this->date_end!=''?"'".$this->db->idate($this->date_end)."'":'null');
		$sql.= ", ".($this->progress!=''?$this->progress:0);
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_CREATE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;

        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_projet,";
		$sql.= " t.fk_task_parent,";
		$sql.= " t.label,";
		$sql.= " t.description,";
		$sql.= " t.duration_effective,";
		$sql.= " t.dateo,";
		$sql.= " t.datee,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_valid,";
		$sql.= " t.fk_statut,";
		$sql.= " t.progress,";
		$sql.= " t.priority,";
		$sql.= " t.note_private,";
		$sql.= " t.note_public";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
                $this->ref					= $obj->rowid;
				$this->fk_project			= $obj->fk_projet;
				$this->fk_task_parent		= $obj->fk_task_parent;
				$this->label				= $obj->label;
				$this->description			= $obj->description;
				$this->duration_effective	= $obj->duration_effective;
				$this->date_c				= $this->db->jdate($obj->datec);
				$this->date_start			= $this->db->jdate($obj->dateo);
				$this->date_end				= $this->db->jdate($obj->datee);
				$this->fk_user_creat		= $obj->fk_user_creat;
				$this->fk_user_valid		= $obj->fk_user_valid;
				$this->fk_statut			= $obj->fk_statut;
				$this->progress				= $obj->progress;
				$this->priority				= $obj->priority;
				$this->note_private			= $obj->note_private;
				$this->note_public			= $obj->note_public;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_project)) $this->fk_project=trim($this->fk_project);
		if (isset($this->fk_task_parent)) $this->fk_task_parent=trim($this->fk_task_parent);
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->description)) $this->description=trim($this->description);
		if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
		$sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
		$sql.= " fk_task_parent=".(isset($this->fk_task_parent)?$this->fk_task_parent:"null").",";
		$sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
		$sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
		$sql.= " dateo=".($this->date_start!=''?$this->db->idate($this->date_start):'null').",";
		$sql.= " datee=".($this->date_end!=''?$this->db->idate($this->date_end):'null').",";
		$sql.= " progress=".$this->progress;
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
	*   \brief      Delete object in database
    *	\param      user        	User that delete
    *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	*	\return		int				<0 if KO, >0 if OK
	*/
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		$this->db->begin();

		if ($this->hasChildren() > 0)
		{
			dol_syslog(get_class($this)."::delete Can't delete record as it has some child", LOG_WARNING);
			$this->error='ErrorRecordHasChildren';
			$this->db->rollback();
			return 0;
		}

		if (! $error)
		{
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0)
			{
				$this->error='ErrorFailToDeleteLinkedContact';
				//$error++;
				$this->db->rollback();
				return 0;
			}
		}

		// Delete rang of line
		//$this->delRangOfLine($this->id, $this->element);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
		        // Call triggers
		        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		        $interface=new Interfaces($this->db);
		        $result=$interface->run_triggers('TASK_DELETE',$this,$user,$langs,$conf);
		        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        // End call triggers
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *		\brief		Return nb of children
	 *		\return 	<0 if KO, 0 if no children, >0 if OK
	 */
	function hasChildren()
	{
		$ret=0;

		$sql = "SELECT COUNT(*) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE fk_task_parent=".$this->id;

		dol_syslog(get_class($this)."::hasChildren sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		else
		{
			$obj=$this->db->fetch_object($resql);
			if ($obj) $ret=$obj->nb;
		}

		if (! $error)
		{
			return $ret;
		}
		else
		{
			return -1;
		}
	}


	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='projecttask';

		$label=$langs->trans("ShowTask").': '.$this->ref.($this->label?' - '.$this->label:'');

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_projet='';
		$this->fk_task_parent='';
		$this->title='';
		$this->duration_effective='';
		$this->fk_user_creat='';
		$this->statut='';
		$this->note='';
	}

	/**
	 * Return list of tasks for all projects or for one particular project
	 * Sort order is on project, TODO then of position of task, and last on title of first level task
	 * @param	usert		Object user to limit tasks affected to a particular user
	 * @param	userp		Object user to limit projects of a particular user and public projects
	 * @param	projectid	Project id
	 * @param	socid		Third party id
	 * @param	mode		0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 * @return 	array		Array of tasks
	 */
	function getTasksArray($usert=0, $userp=0, $projectid=0, $socid=0, $mode=0)
	{
		global $conf;

		$tasks = array();

		//print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT p.rowid as projectid, p.ref, p.title as plabel, p.public,";
		$sql.= " t.rowid as taskid, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress,";
		$sql.= " t.dateo as date_start, t.datee as date_end";
		if ($mode == 0)
		{
			$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
			$sql.= " WHERE t.fk_projet = p.rowid";
			$sql.= " AND p.entity = ".$conf->entity;
			if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
			if ($projectid) $sql.= " AND p.rowid in (".$projectid.")";
		}
		if ($mode == 1)
		{
			$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
			$sql.= " WHERE p.entity = ".$conf->entity;
			if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
			if ($projectid) $sql.= " AND p.rowid in (".$projectid.")";
		}
		$sql.= " ORDER BY p.ref, t.label";

		//print $sql;
		dol_syslog("Task::getTasksArray sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num)
			{
				$error=0;

				$obj = $this->db->fetch_object($resql);

				if ((! $obj->public) && (is_object($userp)))	// If not public project and we ask a filter on project owned by a user
				{
					if (! $this->getUserRolesForProjectsOrTasks($userp, 0, $obj->projectid, 0))
					{
						$error++;
					}
				}
				if (is_object($usert))							// If we ask a filter on a user affected to a task
				{
					if (! $this->getUserRolesForProjectsOrTasks(0, $usert, $obj->projectid, $obj->taskid))
					{
						$error++;
					}
				}

				if (! $error)
				{
					$tasks[$i]->id           = $obj->taskid;
					$tasks[$i]->ref          = $obj->taskid;
					$tasks[$i]->fk_project   = $obj->projectid;
					$tasks[$i]->projectref   = $obj->ref;
					$tasks[$i]->projectlabel = $obj->plabel;
					$tasks[$i]->label        = $obj->label;
					$tasks[$i]->description  = $obj->description;
					$tasks[$i]->fk_parent    = $obj->fk_task_parent;
					$tasks[$i]->duration     = $obj->duration_effective;
					$tasks[$i]->progress     = $obj->progress;
					$tasks[$i]->public       = $obj->public;
					$tasks[$i]->date_start   = $this->db->jdate($obj->date_start);
					$tasks[$i]->date_end     = $this->db->jdate($obj->date_end);
				}

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $tasks;
	}

	/**
	 * Return list of roles for a user for each projects or each tasks (or a particular project or task)
	 * @param 	userp			Return roles on project for this internal user (task id can't be defined)
	 * @param	usert			Return roles on task for this internal user
	 * @param 	projectid		Project id list separated with , to filter on project
	 * @param 	taskid			Task id to filter on a task
	 * @return 	array			Array (projectid => 'list of roles for project' or taskid => 'list of roles for task')
	 */
	function getUserRolesForProjectsOrTasks($userp,$usert,$projectid='',$taskid=0)
	{
		$arrayroles = array();

		dol_syslog("Task::getUserRolesForProjectsOrTasks userp=".is_object($userp)." usert=".is_object($usert)." projectid=".$projectid." taskid=".$taskid);

		// We want role of user for a projet or role of user for a task. Both are not possible.
		if (empty($userp) && empty($usert))
		{
			$this->error="CallWithWrongParameters";
			return -1;
		}
		if (! empty($userp) && ! empty($usert))
		{
			$this->error="CallWithWrongParameters";
			return -1;
		}

		/* Liste des taches et role sur les projets ou taches */
		$sql = "SELECT pt.rowid as pid, ec.element_id, ctc.code, ctc.source";
		if ($userp) $sql.= " FROM ".MAIN_DB_PREFIX."projet as pt";
		if ($usert) $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE pt.rowid = ec.element_id";
		if ($userp) $sql.= " AND ctc.element = 'project'";
		if ($usert) $sql.= " AND ctc.element = 'project_task'";
		$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
		if ($userp) $sql.= " AND ec.fk_socpeople = ".$userp->id;
		if ($usert) $sql.= " AND ec.fk_socpeople = ".$usert->id;
		$sql.= " AND ec.statut = 4";
		$sql.= " AND ctc.source = 'internal'";
		if ($projectid)
		{
			if ($userp) $sql.= " AND pt.rowid in (".$projectid.")";
			if ($usert) $sql.= " AND pt.fk_projet in (".$projectid.")";
		}
		if ($taskid)
		{
			if ($userp) $sql.= " ERROR SHOULD NOT HAPPENS";
			if ($usert) $sql.= " AND pt.rowid = ".$taskid;
		}
		//print $sql;

		dol_syslog("Task::getUserRolesForProjectsOrTasks sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if (empty($arrayroles[$obj->pid])) $arrayroles[$obj->pid] = $obj->code;
				else $arrayroles[$obj->pid].=','.$obj->code;
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $arrayroles;
	}


	/**
	 *      \brief      Return list of id of contacts of task
	 *      \return     array		Array of id of contacts
	 */
	function getListContactId($source='internal')
	{
		$contactAlreadySelected = array();
		$tab = $this->liste_contact(-1,$source);
		//var_dump($tab);
		$num=sizeof($tab);
		$i = 0;
		while ($i < $num)
		{
			if ($source == 'thirdparty') $contactAlreadySelected[$i] = $tab[$i]['socid'];
			else  $contactAlreadySelected[$i] = $tab[$i]['id'];
			$i++;
		}
		return $contactAlreadySelected;
	}


	/**
	 *    \brief     Add time spent
	 *    \param     user           user id
	 *    \param     notrigger	    0=launch triggers after, 1=disable triggers
	 */
	function addTimeSpent($user, $notrigger=0)
	{
		global $conf,$langs;

		$ret = 0;

		// Clean parameters
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (";
		$sql.= "fk_task";
		$sql.= ", task_date";
		$sql.= ", task_duration";
		$sql.= ", fk_user";
		$sql.= ", note";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", '".$this->db->idate($this->timespent_date)."'";
		$sql.= ", ".$this->timespent_duration;
		$sql.= ", ".$this->timespent_fk_user;
		$sql.= ", ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$task_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task_time");
			$ret = $task_id;

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_TIMESPENT_CREATE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::addTimeSpent error -1 ".$this->error,LOG_ERR);
			$ret = -1;
		}

		if ($ret >= 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective + '".price2num($this->timespent_duration)."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
				$ret = -2;
			}
		}

		return $ret;
	}

    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetchTimeSpent($id)
    {
    	global $langs;

        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_task,";
		$sql.= " t.task_date,";
		$sql.= " t.task_duration,";
		$sql.= " t.fk_user,";
		$sql.= " t.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetchTimeSpent sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->timespent_id			= $obj->rowid;
				$this->id					= $obj->fk_task;
				$this->timespent_date		= $obj->task_date;
				$this->timespent_duration	= $obj->task_duration;
				$this->timespent_user		= $obj->fk_user;
				$this->timespent_note		= $obj->note;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchTimeSpent ".$this->error, LOG_ERR);
            return -1;
        }
    }

	/**
	 *    \brief     Update time spent
	 *    \param     user           User id
	 *    \param     notrigger	    0=launch triggers after, 1=disable triggers
	 */
	function updateTimeSpent($user, $notrigger=0)
	{
		$ret = 0;

		// Clean parameters
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

		$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
		$sql.= " task_date = '".$this->db->idate($this->timespent_date)."',";
		$sql.= " task_duration = ".$this->timespent_duration.",";
		$sql.= " fk_user = ".$this->timespent_fk_user.",";
		$sql.= " note = ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
		$sql.= " WHERE rowid = ".$this->timespent_id;

		dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_TIMESPENT_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
			$ret = 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::updateTimeSpent error -1 ".$this->error,LOG_ERR);
			$ret = -1;
		}

		if ($ret == 1 && ($this->timespent_old_duration != $this->timespent_duration))
		{
			$newDuration = $this->timespent_duration - $this->timespent_old_duration;

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective + '".$newDuration."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
				$ret = -2;
			}
		}

		return $ret;
	}

	/**
	 *    \brief      Delete time spent
	 *    \param      user        	User that delete
	 *    \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *    \return		int			<0 if KO, >0 if OK
	 */
	function delTimeSpent($user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time";
		$sql.= " WHERE rowid = ".$this->timespent_id;

		dol_syslog(get_class($this)."::delTimeSpent sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
		        // Call triggers
		        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		        $interface=new Interfaces($this->db);
		        $result=$interface->run_triggers('TASK_TIMESPENT_DELETE',$this,$user,$langs,$conf);
		        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        // End call triggers
			}
		}

		if (! $error)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective - '".$this->timespent_duration."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::delTimeSpent sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$result = 0;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -3 ".$this->error, LOG_ERR);
				$result = -2;
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delTimeSpent ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

}
?>