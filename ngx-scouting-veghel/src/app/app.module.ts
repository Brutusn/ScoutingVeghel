import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { SvSharedModule } from './shared/shared.module';
import { RouterModule } from '@angular/router';
import { svRoutes } from './app.routes';
import { HomeModule } from './pages/home/home.module';

// TODO: Create routes

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    RouterModule.forRoot(svRoutes),
    SvSharedModule,
    HomeModule,
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
