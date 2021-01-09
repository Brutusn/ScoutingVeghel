import { NgModule } from '@angular/core';
import { SvHeaderModule } from './sv-header/sv-header.module';

@NgModule({
  imports: [SvHeaderModule],
  exports: [SvHeaderModule],
})
export class SvSharedModule {}
