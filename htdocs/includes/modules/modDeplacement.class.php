<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\defgroup   deplacement     Module trips
 *	\brief      Module pour gerer les deplacements et notes de frais
 *	\version	$Id: modDeplacement.class.php,v 1.29 2011/07/31 23:28:12 eldy Exp $
 */

/**
 *	\file       htdocs/includes/modules/modDeplacement.class.php
 *	\ingroup    deplacement
 *	\brief      Fichier de description et activation du module Deplacement et notes de frais
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modDeplacement
 *	\brief      Classe de description et activation du module Deplacement
 */
class modDeplacement extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modDeplacement($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 75 ;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des notes de frais et deplacements";		// Si traduction Module75Desc non trouvee

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "trip";

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array();
		$this->langfiles = array("companies","trips");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'deplacement';

		$this->rights[1][0] = 171;
		$this->rights[1][1] = 'Lire les deplacements';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 172;
		$this->rights[2][1] = 'Creer/modifier les deplacements';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 173;
		$this->rights[3][1] = 'Supprimer les deplacements';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';

		$this->rights[4][0] = 178;
		$this->rights[4][1] = 'Exporter les deplacements';
		$this->rights[4][2] = 'd';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'export';

		// Exports
		$r=0;

		$r++;
		$this->export_code[$r]='trips_'.$r;
		$this->export_label[$r]='ListTripsAndExpenses';
		$this->export_permission[$r]=array(array("deplacement","export"));
        $this->export_fields_array[$r]=array('d.rowid'=>"TripId",'d.type'=>"Type",'d.km'=>"FeesKilometersOrAmout",'d.note'=>'NotePrivate','d.note_public'=>'NotePublic','s.nom'=>'ThirdParty','u.name'=>'Lastname','u.firstname'=>'Firstname','d.dated'=>"Date");
        $this->export_entities_array[$r]=array('d.rowid'=>"Trip",'d.type'=>"Trip",'d.km'=>"Trip",'d.note'=>'Trip','d.note_public'=>'Trip','s.nom'=>'company','u.name'=>'user','u.firstname'=>'user','d.dated'=>"Date");

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .=', '.MAIN_DB_PREFIX.'deplacement as d';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON d.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' WHERE d.fk_user = u.rowid';
		$this->export_sql_end[$r] .=' AND d.entity = '.$conf->entity;
	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		// Permissions
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);

	}
}
?>
