/**
 * Renderer/ItemObject.js
 *
 * Manage Items in ground
 *
 * This file is part of ROBrowser, Ragnarok Online in the Web Browser (http://www.robrowser.com/).
 *
 * @author Vincent Thibault
 */
define(['DB/DBManager', './EntityManager', './Entity/Entity', 'Core/Client'],
function(   DB,            EntityManager,            Entity,        Client )
{
	"use strict";


	/**
	 * Find an Entity and return its index
	 *
	 * @param {number} gid
	 * @param {number} itemid
	 * @param {boolean} identify
	 * @param {number} count
	 * @param {number} x
	 * @param {number} y
	 * @param {number} z
	 */
	function Add( gid, itemid, identify, count, x, y, z )
	{
		var it     = DB.getItemInfo(itemid);
		var path   = DB.getItemPath(itemid);
		var entity = new Entity();
		var name   = identify ? it.identifiedDisplayName : it.unidentifiedDisplayName;

		entity.GID          = gid;
		entity.objecttype   = Entity.TYPE_ITEM;
		entity.position[0]  = x;
		entity.position[1]  = y;
		entity.position[2]  = z;

		entity.display.load = entity.display.TYPE.COMPLETE;
		entity.display.name = DB.msgstringtable[183].replace('%s', name).replace('%d', count);

		Client.loadFile(path + ".act", null, null, []);
		Client.loadFile(path + ".spr", function(){
			entity.files.body.spr = path + ".spr";
			entity.files.body.act = path + ".act";

			entity.display.update('#FFEF94');
			EntityManager.add(entity);
		}, null, []);
	}


	/**
	 * Remove an object from ground
	 *
	 * @param {number} gid
	 */
	function Remove( gid )
	{
		EntityManager.remove(gid);
	}


	/**
	 * Export
	 */
	return {
		add:    Add,
		remove: Remove
	};
});