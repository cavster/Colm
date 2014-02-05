package com.example.colmandpalsnuigapp;

import android.os.Bundle;
import android.content.Intent;
import android.view.View;
import android.view.View.OnClickListener;
import android.app.Activity;
import android.view.Menu;

public class MainPage extends Activity implements OnClickListener {

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_main_page);
		View Mapbutton=findViewById(R.id.map);
		Mapbutton.setOnClickListener(this);
		View UsefulLinksButton=findViewById(R.id.Useful_Links);
		UsefulLinksButton.setOnClickListener(this);
		View TimetableButton=findViewById(R.id.TimeTable);
		TimetableButton.setOnClickListener(this);
		View HelpButton=findViewById(R.id.help);
		HelpButton.setOnClickListener(this);
		View AboutUseButton=findViewById(R.id.about_use);
		AboutUseButton.setOnClickListener(this);
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.activity_main_page, menu);
		return true;
	}

	@Override
	public void onClick(View v) {
		// TODO Auto-generated method stub
switch(v.getId()){
case R.id.about_use:
	Intent i= new Intent(this,About.class);
	startActivity(i);
	break;
}

	}

}
