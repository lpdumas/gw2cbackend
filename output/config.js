Resources.Paths = {
	"icons": "assets/images/icons/32x32/"
}

Resources.Icons = {
	"hearts" : { "label" : "Hearts", "url" : Resources.Paths.icons + "hearts.png"},
	"waypoints" : { "label" : "Waypoints", "url" : Resources.Paths.icons + "waypoints.png"},
	"skillpoints" : { "label" : "Skill points", "url" : Resources.Paths.icons + "skillpoints.png"},
	"poi" : { "label" : "Points of intereset", "url" : Resources.Paths.icons + "poi.png"},
	"dungeons" : { "label" : "Dungeons", "url" : Resources.Paths.icons + "dungeon.png"},
	"asurasgates" : { "label" : "Asuras' gates", "url" : Resources.Paths.icons + "asuraGate.png"},
}

Areas = [
	{ name : "Divinity's Reach", rangeLvl : "",
		summary : {
			"hearts" : 0,
			"waypoints" : 13,
			"skillpoints" : 0,
			"poi" : 20,
			"dungeons" : 0
		},
		neLat : 43.1959100116, neLng : -31.5197753906, swLat : 33.4497765831, swLng : -45.9558105469
	},
	{ name : "Queensdale", rangeLvl : "1-17",
		summary : {
			"hearts" : 17,
			"waypoints" : 16,
			"skillpoints" : 7,
			"poi" : 21,
			"dungeons" : 1
		},
		neLat : 33.3855862689, neLng : -23.8623046875, swLat : 18.4066547139, swLng : -48.2739257812
	},
	{ name : "Kessex Hills", rangeLvl : "15-25",
		summary : {
			"hearts" : 14,
			"waypoints" : 18,
			"skillpoints" : 5,
			"poi" : 16,
			"dungeons" : 0
		},
		neLat : 8.36495262654, neLng : -23.5546875, swLat : 4.5983272031, swLng : -51.1743164062
	},
	{ name : "Gendarran Fields", rangeLvl : "25-35",
		summary : {
			"hearts" : 11,
			"waypoints" : 2,
			"skillpoints" : 7,
			"poi" : 15,
			"dungeons" : 0
		},
		neLat : 29.7658345526, neLng : 5.685546875, swLat : 17.5765657098, swLng : -22.8842773438
	},
	{ name : "Black Citadel", rangeLvl : "",
		summary : {
			"hearts" : 0,
			"waypoints" : 12,
			"skillpoints" : 0,
			"poi" : 18,
			"dungeons" : 0
		},
		neLat : 20.7869305926, neLng : 57.9418945312, swLat : 11.0813846024, swLng : 47.900390625
	},
	{ name : "Plains of Ashford", rangeLvl : "1-15",
		summary : {
			"hearts" : 16,
			"waypoints" : 18,
			"skillpoints" : 5,
			"poi" : 26,
			"dungeons" : 1
		},
		neLat : 21.7646014057, neLng : 85.6823730469, swLat : 7.98307772024, swLng : 58.7329101562
	},
	{ name : "Diessa Plateau", rangeLvl : "15-25",
		summary : {
			"hearts" : 15,
			"waypoints" : 19,
			"skillpoints" : 8,
			"poi" : 21,
			"dungeons" : 0
		},
		neLat : 35.54116628, neLng : 71.4770507812, swLat : 21.4632934419, swLng : 47.373046875
	},
	{ name : "Hoelbrak", rangeLvl : "",
		summary : {
			"hearts" : 0,
			"waypoints" : 14,
			"skillpoints" : 0,
			"poi" : 24,
			"dungeons" : 0
		},
		neLat : 22.9078034511, neLng : 34.4970703125, swLat : 12.747516275, swLng : 21.2805175781
	},
	{ name : "Wayfarer Foothills", rangeLvl : "1-15",
		summary : {
			"hearts" : 16,
			"waypoints" : 17,
			"skillpoints" : 8,
			"poi" : 18,
			"dungeons" : 0
		},
		neLat : 34.7686914576, neLng : 46.5380859375, swLat : 8.26585505288, swLng : 35.7495117188
	},
	{ name : "Snowden Drifts", rangeLvl : "15-25",
		summary : {
			"hearts" : 13,
			"waypoints" : 18,
			"skillpoints" : 6,
			"poi" : 20,
			"dungeons" : 0
		},
		neLat : 35.8979501934, neLng : 34.4970703125, swLat : 23.956136334, swLng : 6.61376953125
	},
	{ name : "Lion's Arch", rangeLvl : "",
		summary : {
			"hearts" : 0,
			"waypoints" : 13,
			"skillpoints" : 0,
			"poi" : 20,
			"dungeons" : 0
		},
		neLat : 17.0252726854, neLng : 5.52612304688, swLat : 6.26380486376, swLng : -10.0990234375
	}
]

Markers.hearts = [
	{ "id" : 1, "lat" : 29.1905328323, "lng" : -4.384765625, "title" : "Help Farmer Diah", "desc" : "Help Diah by watering corn, stomping wurm mounds, feeding cattle, and defending the fields."},
	{ "id" : 2, "lat" : 29.4395975666, "lng" : -46.6369628906, "title" : "Help Farmer Test George", "desc" : "Make the area around the pumping station safe for Farmer George."},
	{ "id" : 3, "lat" : 31.5129958575, "lng" : -46.5490722656, "title" : "Help Farmer Eda", "desc" : "Eda could use some help in her orchard, especially with the spider infestation."},
	{ "id" : 4, "lat" : 29.8327467992, "lng" : -38.4304199219, "title" : "Assist the Seraph at Shaemoor Garrison", "desc" : "Drive back centaur forces and secure remaining farmlands."},
	{ "id" : 5, "lat" : 20.8896075104, "lng" : -26.0703125, "title" : "Test", "desc" : "test desc"},
	{ "id" : 6, "lat" : 23.2312509239, "lng" : -3.44287109375, "title" : "a", "desc" : "blablabli"},
	{ "id" : 7, "lat" : 26.2047342671, "lng" : -30.0146484375, "title" : "b", "desc" : ""},
	{ "id" : 8, "lat" : 22.0143606531, "lng" : -38.14453125, "title" : "c", "desc" : ""},
	{ "id" : 10, "lat" : 19.9423691895, "lng" : 65.1708984375, "title" : "", "desc" : ""}
]
